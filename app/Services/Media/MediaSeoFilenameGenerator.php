<?php

namespace App\Services\Media;

use Illuminate\Support\Str;

class MediaSeoFilenameGenerator
{
    public static function generate(?string $originalName, ?string $context = null): string
    {
        $base = Str::slug(pathinfo((string) $originalName, PATHINFO_FILENAME)) ?: 'medca-media';
        if ($context !== null && $context !== '') {
            $prefix = Str::slug($context);
            if ($prefix !== '') {
                $base = $prefix.'-'.$base;
            }
        }

        $suffix = substr((string) time(), -6);

        return Str::limit($base.'-'.$suffix, 80, '');
    }
}
