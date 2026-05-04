<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MarketingSetting extends Model
{
    protected $fillable = [
        'ga4_measurement_id',
        'ga4_property_id',
        'google_ads_aw_id',
        'meta_pixel_id',
    ];

    public static function current(): self
    {
        return Cache::rememberForever('marketing.settings.singleton', function (): self {
            return self::query()->firstOrCreate([], []);
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget('marketing.settings.singleton');
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
