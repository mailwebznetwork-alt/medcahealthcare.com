<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Generates on-disk placeholder JPEGs for launch seeding (replace in Operations → Services later).
 */
final class MedcaLaunchMedia
{
    public static function featuredPath(string $serviceCode): string
    {
        return self::ensureJpeg("featured-{$serviceCode}.jpg", 1200, 630, $serviceCode);
    }

    /**
     * @return list<string> Storage paths relative to public disk.
     */
    public static function galleryPaths(string $serviceCode, int $count = 3): array
    {
        $paths = [];
        for ($i = 1; $i <= $count; $i++) {
            $paths[] = self::ensureJpeg("gallery-{$serviceCode}-{$i}.jpg", 800, 600, "{$serviceCode} {$i}");
        }

        return $paths;
    }

    private static function ensureJpeg(string $filename, int $width, int $height, string $label): string
    {
        $relative = 'medca/launch/'.$filename;
        $disk = Storage::disk('public');

        if ($disk->exists($relative)) {
            return $relative;
        }

        $disk->makeDirectory('medca/launch');

        if (! function_exists('imagecreatetruecolor')) {
            File::put($disk->path($relative), base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
                true
            ) ?: '');

            return $relative;
        }

        $image = imagecreatetruecolor($width, $height);
        $navy = imagecolorallocate($image, 0, 31, 92);
        $gold = imagecolorallocate($image, 197, 160, 89);
        $white = imagecolorallocate($image, 248, 250, 252);
        imagefill($image, 0, 0, $navy);
        imagefilledrectangle($image, 24, 24, $width - 24, $height - 24, $white);
        imagefilledrectangle($image, 24, 24, $width - 24, 72, $gold);
        imagestring($image, 5, 40, (int) (($height / 2) - 8), substr($label, 0, 40), $navy);
        imagejpeg($image, $disk->path($relative), 88);
        imagedestroy($image);

        return $relative;
    }
}
