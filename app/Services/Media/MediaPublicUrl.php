<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\Storage;

class MediaPublicUrl
{
    /**
     * Absolute URL for a file on the public disk, optionally via CDN.
     */
    public static function forPath(?string $relativePath): string
    {
        if ($relativePath === null || $relativePath === '') {
            return '';
        }

        $path = ltrim($relativePath, '/');

        if (config('media.cdn.enabled', false) && ($cdn = (string) config('media.cdn.url', '')) !== '') {
            return rtrim($cdn, '/').'/storage/'.$path;
        }

        return Storage::disk('public')->url($path);
    }
}
