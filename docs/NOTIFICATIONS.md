# Notification Engine

Unified dispatch for in-app, email, and SMS notifications. One call fans a
logical event out across every channel a recipient is eligible for, instead of
each caller hand-wiring channels.

## Sending

```php
service('notifier')->send(
    $userId,                 // int or int[]
    'leave.approved',        // event slug — keys the email + SMS templates
    [
        'title'      => 'Leave approved',                 // in-app title (required for in-app)
        'body'       => 'Your leave from 1–3 Aug is approved.', // in-app body
        'link'       => 'erp/leave',                      // relative → site_url(), or full URL
        'leave_name' => 'Annual leave',                   // extra {token} for templates
    ],
    ['inapp', 'email', 'sms'] // OPTIONAL — omit to honour the user's saved preferences
);
```

- `title`/`body`/`link` and any scalar in `$data` become `{token}` replacements.
- User tokens are always available: `{firstname} {lastname} {fullname} {email}`.

## Channels

| Channel | How it delivers | Template source |
|---------|-----------------|-----------------|
| in-app  | synchronous insert into `ci_notifications` | none (uses `title`/`body`) |
| email   | queued on the **`emails`** beanstalkd tube | `ci_email_template.template_code = event`, `status = 1` |
| sms     | queued on the **`sms`** beanstalkd tube | `ci_sms_template.subject = event` (or `$data['sms_template']`) |

A channel with no matching template is skipped silently — partial template
coverage never blocks the in-app notification. The `php spark queue:worker`
process drains both tubes.

## Preferences

When `$channels` is omitted, delivery follows `ci_user_notification_prefs`:

1. `(user_id, event)` row — per-event override, if present
2. `(user_id, '*')` row — the user's global default
3. Hard defaults — in-app **on**, email **on**, SMS **off**

Absence of any row = hard defaults, so no seeding is required.

## SMS provider

`service('smsProvider')` selects the gateway from system settings
(Super Admin → Settings → SMS):

- `sms_active` — master switch; off ⇒ `NullSmsProvider` (no-op, logs only)
- `sms_gateway` — driver key (`africastalking`, default)
- `sms_username`, `sms_api_key` (encrypted), `sms_sender_id`, `sms_environment`

A driver missing credentials also degrades to the null provider, so callers
never branch on configuration state. Add a gateway by implementing
`App\Libraries\Sms\SmsProviderInterface` and registering it in
`Services::smsProvider()`.
