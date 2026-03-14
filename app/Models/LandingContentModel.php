<?php
namespace App\Models;

use CodeIgniter\Model;

class LandingContentModel extends Model
{
    protected $table      = 'ci_landing_content';
    protected $primaryKey = 'id';

    protected $allowedFields = ['section', 'content_key', 'content_value', 'content_json', 'updated_at'];

    protected $useTimestamps = false;

    // ---------------------------------------------------------------
    // Helper methods
    // ---------------------------------------------------------------

    /**
     * Get all content rows for a given section.
     */
    public function getSection(string $section): array
    {
        return $this->where('section', $section)->findAll();
    }

    /**
     * Get a single scalar value by section + key.
     */
    public function getValue(string $section, string $key): ?string
    {
        $row = $this->where('section', $section)
                     ->where('content_key', $key)
                     ->first();

        return $row ? $row['content_value'] : null;
    }

    /**
     * Get a JSON value (decoded) by section + key.
     */
    public function getJson(string $section, string $key): ?array
    {
        $row = $this->where('section', $section)
                     ->where('content_key', $key)
                     ->first();

        if (! $row || empty($row['content_json'])) {
            return null;
        }

        return json_decode($row['content_json'], true);
    }

    /**
     * Upsert a scalar value for section + key.
     */
    public function setValue(string $section, string $key, string $value): void
    {
        $existing = $this->where('section', $section)
                         ->where('content_key', $key)
                         ->first();

        $data = [
            'section'       => $section,
            'content_key'   => $key,
            'content_value' => $value,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }
    }

    /**
     * Upsert a JSON value for section + key.
     */
    public function setJson(string $section, string $key, array $data): void
    {
        $existing = $this->where('section', $section)
                         ->where('content_key', $key)
                         ->first();

        $row = [
            'section'      => $section,
            'content_key'  => $key,
            'content_json' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->update($existing['id'], $row);
        } else {
            $this->insert($row);
        }
    }
}
