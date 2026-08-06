<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries;

/**
 * Atomic idempotency claims for the notification pipeline (P8).
 *
 * claim() answers "am I the first to send this within the window?" using a
 * unique INSERT: expired claim for the key is dropped, then
 * INSERT ... ON CONFLICT DO NOTHING — exactly one concurrent caller inserts.
 *
 * Used at two layers:
 *  - dispatch (Notifier): same user + channel + rendered content inside the
 *    window ⇒ duplicate business event (double-click, double submit) — skip.
 *  - delivery (QueueWorker sms): claim before the paid provider call so a
 *    beanstalkd redelivery after a crash-mid-send can never double-bill;
 *    release() on a clean failure so genuine retries still go out.
 */
class NotificationDedupe
{
    public const TABLE = 'ci_notification_dedupe';

    /** Dispatch windows (seconds): repeats inside these are treated as dupes. */
    public const WINDOW_INAPP = 600;
    public const WINDOW_EMAIL = 600;
    public const WINDOW_SMS   = 3600;

    /** Delivery claims guard queue redelivery — keep them a full day. */
    public const WINDOW_DELIVERY = 86400;

    /**
     * Try to claim a key. True = claimed (proceed to send), false = a live
     * claim already exists (skip). Fail-open on infrastructure errors — losing
     * dedupe is better than losing notifications; the anomaly is logged.
     */
    public static function claim(string $key, string $channel, int $windowSeconds): bool
    {
        try {
            $db = \Config\Database::connect();

            // Drop only an EXPIRED claim for this key, then race on the insert.
            $db->query(
                'DELETE FROM ' . self::TABLE . ' WHERE dedupe_key = ? AND created_at < ?',
                [$key, date('Y-m-d H:i:s', time() - $windowSeconds)]
            );
            $db->query(
                'INSERT INTO ' . self::TABLE . ' (dedupe_key, channel, created_at) VALUES (?, ?, ?) ON CONFLICT (dedupe_key) DO NOTHING',
                [$key, $channel, date('Y-m-d H:i:s')]
            );
            $claimed = $db->affectedRows() === 1;

            // Opportunistic global prune (~1% of calls) so the table stays small.
            if (random_int(1, 100) === 1) {
                $db->query(
                    'DELETE FROM ' . self::TABLE . ' WHERE created_at < ?',
                    [date('Y-m-d H:i:s', time() - self::WINDOW_DELIVERY)]
                );
            }

            return $claimed;
        } catch (\Throwable $e) {
            log_message('warning', 'NotificationDedupe unavailable (' . $e->getMessage() . ') — sending without dedupe');
            return true;
        }
    }

    /** Release a claim after a CLEAN send failure so a retry can send. */
    public static function release(string $key): void
    {
        try {
            \Config\Database::connect()
                ->query('DELETE FROM ' . self::TABLE . ' WHERE dedupe_key = ?', [$key]);
        } catch (\Throwable $e) {
            log_message('warning', 'NotificationDedupe release failed: ' . $e->getMessage());
        }
    }
}
