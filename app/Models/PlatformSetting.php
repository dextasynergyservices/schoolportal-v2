<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    /** @var list<string> */
    protected $fillable = ['key', 'value', 'type', 'description'];

    // ── Defaults used everywhere (single source of truth) ──────────

    /** @var array<string, mixed> */
    public const DEFAULTS = [
        'platform_name' => 'DX-SchoolPortal',
        'default_free_ai_credits' => 15,
        'maintenance_mode' => false,
        'maintenance_message' => '',
        'allowed_file_types' => 'pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif,webp',
        'max_upload_size_mb' => 10,
        'credit_price_per_5' => 1000,
    ];

    /** @var array<string, string> */
    public const TYPES = [
        'platform_name' => 'string',
        'default_free_ai_credits' => 'integer',
        'maintenance_mode' => 'boolean',
        'maintenance_message' => 'string',
        'allowed_file_types' => 'string',
        'max_upload_size_mb' => 'integer',
        'credit_price_per_5' => 'integer',
    ];

    // ── Cache helpers ───────────────────────────────────────────────

    private static function cacheKey(string $key): string
    {
        return "platform_setting:{$key}";
    }

    // ── Public API ──────────────────────────────────────────────────

    /**
     * Get a platform setting value, returning a typed value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $raw = Cache::remember(static::cacheKey($key), 300, function () use ($key): ?string {
            return static::where('key', $key)->value('value');
        });

        if ($raw === null) {
            return $default ?? (static::DEFAULTS[$key] ?? null);
        }

        return static::cast($key, $raw);
    }

    /**
     * Persist a setting and clear its cache entry.
     */
    public static function set(string $key, mixed $value): void
    {
        $type = static::TYPES[$key] ?? 'string';

        $stored = match ($type) {
            'boolean' => (is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOLEAN)) ? '1' : '0',
            'integer' => (string) (int) $value,
            default => (string) $value,
        };

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'type' => $type],
        );

        Cache::forget(static::cacheKey($key));
    }

    /**
     * Return all known settings as a key → typed-value map.
     * Reads fresh from DB (no caching) for admin display.
     */
    public static function allValues(): array
    {
        $rows = static::whereIn('key', array_keys(static::DEFAULTS))->get()->keyBy('key');

        $result = [];
        foreach (static::DEFAULTS as $k => $default) {
            $raw = $rows->get($k)?->value;
            $result[$k] = $raw !== null ? static::cast($k, $raw) : $default;
        }

        return $result;
    }

    // ── Internal ────────────────────────────────────────────────────

    private static function cast(string $key, string $raw): mixed
    {
        $type = static::TYPES[$key] ?? 'string';

        return match ($type) {
            'integer' => (int) $raw,
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            default => $raw,
        };
    }
}
