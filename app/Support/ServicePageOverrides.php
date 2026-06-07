<?php

namespace App\Support;

use App\Models\Page;

/**
 * Optional page-level overrides for fields inherited from Operations → Services.
 */
final class ServicePageOverrides
{
    public static function seoOverride(Page $page): bool
    {
        return self::flag($page, 'seo_override');
    }

    public static function aeoOverride(Page $page): bool
    {
        return self::flag($page, 'aeo_override');
    }

    public static function geoOverride(Page $page): bool
    {
        return self::flag($page, 'geo_override');
    }

    private static function flag(Page $page, string $key): bool
    {
        $meta = $page->deployment_meta_json;
        if (! is_array($meta)) {
            return false;
        }

        $master = $meta['service_master'] ?? null;
        if (! is_array($master)) {
            return false;
        }

        return (bool) ($master[$key] ?? false);
    }
}
