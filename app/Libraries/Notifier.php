<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries;

use App\Models\NotificationModel;

/**
 * Unified notification dispatcher.
 *
 * A single entry point that fans one logical event out across up to three
 * channels — in-app, email, SMS — instead of every caller hand-wiring each
 * channel (as create_notification(), NotificationModel::notify() and ad-hoc
 * inserts do today).
 *
 *   service('notifier')->send(
 *       $userId,                 // int or int[]
 *       'leave.approved',        // event slug (keys email/SMS templates)
 *       [                        // template tokens + in-app title/body
 *           'title'      => 'Leave approved',
 *           'body'       => 'Your leave from 1–3 Aug was approved.',
 *           'link'       => 'erp/leave',
 *           'leave_name' => 'Annual leave',
 *       ],
 *       ['inapp', 'email', 'sms'] // optional; omit to use per-user preferences
 *   );
 *
 * Delivery model:
 *   - in-app  → written synchronously to ci_notifications (fast, local).
 *   - email   → looked up in ci_email_template by template_code = event,
 *               rendered, and queued on the "emails" tube.
 *   - sms     → looked up in ci_sms_template by subject = event (or
 *               $data['sms_template']), rendered, and queued on the "sms" tube.
 *
 * Channels with no matching template are skipped silently, so partial template
 * coverage never blocks the in-app notification.
 */
class Notifier
{
    /** Hard defaults when a user has no preference row. */
    private const DEFAULTS = ['inapp' => true, 'email' => true, 'sms' => false];

    private \CodeIgniter\Database\BaseConnection $db;
    private NotificationModel $notifications;
    private Queue $queue;

    public function __construct()
    {
        $this->db            = \Config\Database::connect();
        $this->notifications = new NotificationModel();
        $this->queue         = new Queue();
    }

    /**
     * Dispatch a notification to one or many users.
     *
     * @param int|int[]     $to       Recipient user id(s).
     * @param string        $event    Event slug used to resolve templates + prefs.
     * @param array         $data     Template tokens. Recognised in-app keys:
     *                                title, body, link. Optional: sms_template.
     * @param string[]|null $channels Explicit channel allow-list, or null to
     *                                fall back to each user's stored preferences.
     */
    public function send($to, string $event, array $data = [], ?array $channels = null): void
    {
        $userIds = array_values(array_unique(array_filter(array_map(
            'intval',
            is_array($to) ? $to : [$to]
        ))));

        if ($userIds === []) {
            return;
        }

        $emailTemplate = $this->emailTemplate($event);
        $smsTemplate   = $this->smsTemplate($data['sms_template'] ?? $event);

        foreach ($userIds as $userId) {
            $user = $this->user($userId);
            if ($user === null) {
                continue;
            }

            $tokens  = $this->tokens($user, $data);
            $allowed = $this->channelsFor($userId, $event, $channels);

            if ($allowed['inapp']) {
                $this->sendInApp($user, $data, $tokens);
            }
            if ($allowed['email'] && $emailTemplate !== null && ! empty($user['email'])) {
                $this->queueEmail($user['email'], $emailTemplate, $tokens);
            }
            if ($allowed['sms'] && $smsTemplate !== null && ! empty($user['contact_number'])) {
                $this->queueSms((int) $user['user_id'], $user['contact_number'], $smsTemplate, $tokens);
            }
        }
    }

    // ------------------------------------------------------------------
    //  Channel senders
    // ------------------------------------------------------------------

    private function sendInApp(array $user, array $data, array $tokens): void
    {
        $title = $this->render((string) ($data['title'] ?? ''), $tokens);
        if ($title === '') {
            return; // in-app notifications require a title
        }

        $link = (string) ($data['link'] ?? '');
        if ($link !== '' && strpos($link, 'http') !== 0) {
            $link = site_url(ltrim($link, '/'));
        }

        $this->notifications->notify(
            (int) $user['user_id'],
            isset($user['company_id']) ? (int) $user['company_id'] : null,
            $title,
            $this->render((string) ($data['body'] ?? ''), $tokens),
            $link
        );
    }

    private function queueEmail(string $to, array $template, array $tokens): void
    {
        $this->queue->push('emails', [
            'to'      => $to,
            'subject' => $this->render((string) $template['subject'], $tokens),
            'body'    => $this->render((string) $template['message'], $tokens),
        ]);
    }

    private function queueSms(int $userId, string $to, array $template, array $tokens): void
    {
        $this->queue->push('sms', [
            'user_id' => $userId,
            'to'      => $to,
            'message' => $this->render((string) $template['message'], $tokens),
        ]);
    }

    // ------------------------------------------------------------------
    //  Resolution helpers
    // ------------------------------------------------------------------

    /**
     * Resolve the effective channels for a user + event.
     *
     * An explicit $channels allow-list always wins. Otherwise preferences are
     * resolved most-specific-first: the (user, event) row, then the (user, '*')
     * global row, then hard defaults.
     *
     * @return array{inapp: bool, email: bool, sms: bool}
     */
    private function channelsFor(int $userId, string $event, ?array $channels): array
    {
        if ($channels !== null) {
            return [
                'inapp' => in_array('inapp', $channels, true),
                'email' => in_array('email', $channels, true),
                'sms'   => in_array('sms', $channels, true),
            ];
        }

        $rows = $this->db->table('ci_user_notification_prefs')
            ->whereIn('event', [$event, '*'])
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return self::DEFAULTS;
        }

        $byEvent = [];
        foreach ($rows as $row) {
            $byEvent[$row['event']] = $row;
        }
        $pref = $byEvent[$event] ?? $byEvent['*'];

        return [
            'inapp' => (int) $pref['inapp'] === 1,
            'email' => (int) $pref['email'] === 1,
            'sms'   => (int) $pref['sms'] === 1,
        ];
    }

    private function user(int $userId): ?array
    {
        return $this->db->table('ci_erp_users')
            ->select('user_id, company_id, first_name, last_name, email, contact_number')
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();
    }

    private function emailTemplate(string $event): ?array
    {
        return $this->db->table('ci_email_template')
            ->where('template_code', $event)
            ->where('status', 1)
            ->get()
            ->getRowArray();
    }

    private function smsTemplate(string $name): ?array
    {
        return $this->db->table('ci_sms_template')
            ->where('subject', $name)
            ->get()
            ->getRowArray();
    }

    /**
     * Merge user attributes with caller data into the token map used for
     * {placeholder} substitution. Caller data wins on key collisions.
     */
    private function tokens(array $user, array $data): array
    {
        $tokens = [
            'firstname' => $user['first_name'] ?? '',
            'lastname'  => $user['last_name'] ?? '',
            'fullname'  => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
            'email'     => $user['email'] ?? '',
        ];

        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $tokens[$key] = (string) $value;
            }
        }

        return $tokens;
    }

    /**
     * Replace {token} placeholders in a template string.
     */
    private function render(string $template, array $tokens): string
    {
        if ($template === '' || strpos($template, '{') === false) {
            return $template;
        }

        $search  = array_map(static fn ($k) => '{' . $k . '}', array_keys($tokens));
        $replace = array_values($tokens);

        return str_replace($search, $replace, $template);
    }
}
