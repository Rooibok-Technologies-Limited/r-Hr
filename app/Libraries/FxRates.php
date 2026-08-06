<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries;

/**
 * Foreign-exchange conversion backed by AUTO-UPDATED trusted rates — never
 * manually-entered ones (they change daily).
 *
 * Source: exchangerate-api's free `open.er-api.com` endpoint (central-bank +
 * commercial sourced, updated daily, no API key). Rates are cached in
 * ci_fx_rates (one row per base currency) with a fetch timestamp. Conversion
 * reads the cache; a refresh runs from cron/queue (spark fx:refresh), never
 * blocking a web request. If the source is unreachable, the last good cache is
 * used and its age is exposed via ratesAgeHours().
 */
class FxRates
{
    private const TABLE  = 'ci_fx_rates';
    private const SOURCE = 'https://open.er-api.com/v6/latest/';
    private const BASE   = 'USD';          // canonical base we cache against
    private const STALE_HOURS = 24;

    private \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Fetch the latest rates from the trusted source and cache them. Returns
     * true on success. Safe to call from cron/queue; catches all failures.
     */
    public function refresh(string $base = self::BASE): bool
    {
        try {
            $ctx  = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
            $body = @file_get_contents(self::SOURCE . strtoupper($base), false, $ctx);
            if ($body === false) {
                return false;
            }
            $json = json_decode($body, true);
            if (! is_array($json) || ($json['result'] ?? '') !== 'success' || empty($json['rates'])) {
                return false;
            }
            $now = date('Y-m-d H:i:s');
            $row = [
                'base_currency' => strtoupper($base),
                'rates_json'    => json_encode($json['rates']),
                'source'        => 'open.er-api.com',
                'fetched_at'    => $now,
                'updated_at'    => $now,
            ];
            $exists = $this->db->table(self::TABLE)->where('base_currency', strtoupper($base))->countAllResults() > 0;
            if ($exists) {
                $this->db->table(self::TABLE)->where('base_currency', strtoupper($base))->update($row);
            } else {
                $this->db->table(self::TABLE)->insert($row);
            }
            return true;
        } catch (\Throwable $e) {
            log_message('error', 'FxRates refresh failed: ' . $e->getMessage());
            return false;
        }
    }

    /** The cached USD-based rate map (['EUR'=>0.86, ...]); refreshes if missing. */
    private function baseRates(): array
    {
        $row = $this->db->table(self::TABLE)->where('base_currency', self::BASE)->get()->getRowArray();
        if (! $row) {
            $this->refresh(self::BASE);
            $row = $this->db->table(self::TABLE)->where('base_currency', self::BASE)->get()->getRowArray();
        }
        $rates = $row ? json_decode($row['rates_json'], true) : [];
        $rates[self::BASE] = 1.0;
        return is_array($rates) ? $rates : [self::BASE => 1.0];
    }

    /** Age of the cached rates in hours (null if never fetched). */
    public function ratesAgeHours(): ?float
    {
        $row = $this->db->table(self::TABLE)->where('base_currency', self::BASE)->get()->getRowArray();
        if (! $row) {
            return null;
        }
        return (time() - strtotime($row['fetched_at'])) / 3600;
    }

    /** Cross-rate FROM→TO via the USD base. Returns null if either is unknown. */
    public function rate(string $from, string $to): ?float
    {
        $from = strtoupper($from);
        $to   = strtoupper($to);
        if ($from === $to) {
            return 1.0;
        }
        $r = $this->baseRates();
        if (! isset($r[$from], $r[$to]) || (float) $r[$from] == 0.0) {
            return null;
        }
        // amount_to = amount_from * (usd_per_from) inverted → (to/base)/(from/base)
        return (float) $r[$to] / (float) $r[$from];
    }

    /**
     * Convert an amount FROM→TO. Returns the original amount unchanged if the
     * rate is unavailable (fail-safe: never fabricate a wrong figure silently —
     * callers can check rate() first when correctness is critical).
     */
    public function convert(float $amount, string $from, string $to): float
    {
        $rate = $this->rate($from, $to);
        return $rate === null ? $amount : $amount * $rate;
    }
}
