<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Store setting edited from the back-office (F-004, F-111). Keys mirror config/storefront.php
 * (e.g. "contact.phone"); a missing or empty setting falls back to that config value.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    private const CACHE_KEY = 'settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetCache());
        static::deleted(fn () => self::forgetCache());
    }

    /**
     * For changes made without model events (a mass delete or update).
     */
    public static function forgetCache(): void
    {
        Cache::memo()->forget(self::CACHE_KEY);
    }

    /**
     * All non-empty settings, cached until one changes. Memoized for the request or job: a page reads settings a few
     * hundred times, and each read of the database cache store would otherwise be a query.
     *
     * @return array<string, string>
     */
    public static function values(): array
    {
        return Cache::memo()->rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->whereNotNull('value')->where('value', '!=', '')->pluck('value', 'key')->all(),
        );
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::values()[$key] ?? $default;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function store(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => filled($value) ? (string) $value : null]);
        }
    }
}
