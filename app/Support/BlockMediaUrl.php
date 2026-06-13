<?php

namespace App\Support;

class BlockMediaUrl
{
    public static function resolve(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    /**
     * @param  array<string, mixed>  $media
     */
    public static function heroBackgroundStyle(array $media): ?string
    {
        $desktop = self::resolve(
            is_string($media['desktop_image'] ?? null) && $media['desktop_image'] !== ''
                ? (string) $media['desktop_image']
                : (is_string($media['fallback_image'] ?? null) ? (string) $media['fallback_image'] : null)
        );

        if ($desktop === null) {
            return null;
        }

        $mobile = self::resolve(
            is_string($media['mobile_image'] ?? null) && $media['mobile_image'] !== ''
                ? (string) $media['mobile_image']
                : null
        ) ?? $desktop;

        $vars = '--hero-bg-desktop:url('.self::cssUrl($desktop).');--hero-bg-mobile:url('.self::cssUrl($mobile).');';

        return $vars.'background-image:linear-gradient(rgba(10,15,28,.72),rgba(10,15,28,.72)),var(--hero-bg-desktop);background-size:cover;background-position:center;';
    }

    private static function cssUrl(string $url): string
    {
        return '"'.str_replace('"', '\\"', $url).'"';
    }

    /**
     * @param  array<string, mixed>  $media
     */
    public static function first(array $media, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            $url = self::resolve(is_string($media[$key] ?? null) ? (string) $media[$key] : null);
            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }
}
