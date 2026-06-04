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
        $url = self::resolve(
            is_string($media['desktop_image'] ?? null) && $media['desktop_image'] !== ''
                ? (string) $media['desktop_image']
                : (is_string($media['fallback_image'] ?? null) ? (string) $media['fallback_image'] : null)
        );

        if ($url === null) {
            return null;
        }

        return 'background-image:linear-gradient(rgba(10,15,28,.72),rgba(10,15,28,.72)),url('.$url.');background-size:cover;background-position:center;';
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
