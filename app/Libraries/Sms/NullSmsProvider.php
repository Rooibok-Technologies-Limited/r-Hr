<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries\Sms;

/**
 * No-op SMS provider.
 *
 * Used when SMS is inactive (`sms_active` off) or credentials are missing.
 * It logs the intended message and reports success so callers never block on
 * an unconfigured gateway. Nothing leaves the server.
 */
class NullSmsProvider implements SmsProviderInterface
{
    public function send(string $to, string $message): bool
    {
        log_message('info', '[SMS:null] Suppressed (SMS inactive). to={to} body={body}', [
            'to'   => $to,
            'body' => mb_substr($message, 0, 160),
        ]);

        return true;
    }
}
