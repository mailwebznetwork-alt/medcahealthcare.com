<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MarketingSetting extends Model
{
    /** @deprecated Stored serialized models break across deploys; kept only for Cache::forget cleanup */
    private const LEGACY_CACHE_KEY = 'marketing.settings.singleton';

    private const CACHE_KEY_ID = 'marketing.settings.singleton_id';

    protected $fillable = [
        'ga4_measurement_id',
        'ga4_property_id',
        'google_ads_aw_id',
        'meta_pixel_id',
    ];

    public static function current(): self
    {
        /** @var int $id */
        $id = Cache::rememberForever(self::CACHE_KEY_ID, function (): int {
            return (int) self::query()->firstOrCreate([], [])->getKey();
        });

        return self::query()->findOrFail($id);
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::LEGACY_CACHE_KEY);
        Cache::forget(self::CACHE_KEY_ID);
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            self::forgetCache();
        });

        static::deleted(function (): void {
            self::forgetCache();
        });
    }
}
