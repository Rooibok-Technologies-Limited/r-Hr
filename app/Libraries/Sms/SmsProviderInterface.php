<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries\Sms;

/**
 * Contract for outbound SMS gateways.
 *
 * Implementations are resolved through service('smsProvider'), which selects
 * the driver from the `sms_gateway` system setting and falls back to a no-op
 * NullSmsProvider when SMS is inactive or unconfigured. This keeps every
 * caller (the queue worker, broadcasts, the Notifier) provider-agnostic.
 */
interface SmsProviderInterface
{
    /**
     * Deliver a single text message.
     *
     * @param string $to      Recipient MSISDN in international format (e.g. +256771234567).
     * @param string $message Plain-text body (already rendered — no template tokens left).
     *
     * @return bool True when the gateway accepted the message for delivery.
     */
    public function send(string $to, string $message): bool;
}
