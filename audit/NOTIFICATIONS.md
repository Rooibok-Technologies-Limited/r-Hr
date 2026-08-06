<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# Notification Coverage (RSP-S8, 2026-08-06)

## Architecture
`service('notifier')->send($to,$event,$data,$channels)` fans out in-app (sync) + email
(`emails` tube) + SMS (`sms` tube). Email template resolved by `template_code = event`;
SMS by `ci_sms_template.subject = event`. SMS degrades to NullSmsProvider (stub verified
active — no real sends). P8 idempotency: dispatch + at-most-once paid SMS (ci_notification_dedupe).

## D-NOTIF-01 (High, FIXED — migration ...000003)
**Every email template shipped with template_code='code1'**, so the Notifier's email lookup
(`where template_code = event`) matched NOTHING — the email channel was silently dead for
every event. Fixed: assigned real event-slug codes to all 13 templates (password.reset,
password.changed, company_registered, leave.approved/rejected/requested, payslip.available,
task.assigned, project.assigned, award.received, ticket.created, job.posted, contact.received).
Verified: 0 'code1' remaining; company_registered now resolves the Warm Welcome template.

## D-NOTIF-02 (Medium, GAP — coverage) — implement as scoped feature
Only ~5 events are wired to notifier->send (company_registered, subscription_active,
subscription_updated, renewal_requested, + one user_id). The mandate's required set is ~25.
MISSING triggers (state changes that matter to a human but send nothing today):
- auth: invitation, email/phone verification, password reset SENT, login-from-new-device
- employer: payout-details changed (sensitive — needs re-auth + audit + notify)
- employee: added / deactivated
- payroll: run created / submitted / approved / rejected / processing started
- disbursement: succeeded / partially-failed / failed / individual-payment-failed
- payslip: available / sent
- funding: low-balance / funding-required; scheduled-run reminder
Each needs: a notifier->send at the state transition + a template (email code + SMS subject).
Templates now exist for leave.*, payslip.available, task/project.assigned — wire the code
to call notifier->send at those transitions.

## Verified sound
- Template-variable safety: 0 templates contain unresolved `{{ }}` / `undefined` (the Notifier
  uses single-brace {token} substitution + always-available {firstname}{lastname}{fullname}{email}).
- SMS: NullSmsProvider active in dev (no real sends); E.164 normalisation in the MoMo/Airtel libs.
- Delivery: queued over beanstalkd (non-blocking); worker consumes emails+sms tubes.

## D-NOTIF-02 progress
- leave.approved / leave.rejected: in-app notification added at Leave::update_leave_status
  (inapp-only — email/SMS already sent by the legacy path; no double-send). Verified live.

## Next (S8 continuation): remaining D-NOTIF-02 triggers (payroll/disbursement/payslip states) + per-template render tests.
