<?php

namespace App\Services\Media;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Throwable;

class MediaUploadProcessor
{
    public function process(UploadedFile $file, ?int $userId = null): Media
    {
        $uuid = (string) Str::uuid();
        $baseDir = 'media/'.$uuid;
        $disk = Storage::disk('public');

        $originalName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $safeBase = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'file';
        if ($safeBase === '') {
            $safeBase = 'file';
        }

        $path = $file->storeAs($baseDir, 'original.'.$ext, 'public');
        if ($path === false) {
            throw new \RuntimeException('Failed to store upload.');
        }

        $publicUrl = MediaPublicUrl::forPath($path);
        $mime = $file->getMimeType() ?? '';
        $fileType = $this->resolveFileType($mime);
        $size = $file->getSize();

        $data = [
            'uuid' => $uuid,
            'file_name' => $originalName,
            'file_path' => $path,
            'file_url' => $publicUrl,
            'file_type' => $fileType,
            'file_size' => $size,
            'title' => null,
            'alt_text' => $fileType === 'image' ? '' : null,
            'description' => null,
            'uploaded_by' => $userId,
        ];

        if ($fileType === 'image' && $this->isProcessableImage($mime, $ext)) {
            $derived = $this->generateImageDerivatives($disk->path($path), $baseDir);
            $data = array_merge($data, $derived);
        }

        return Media::query()->create($data);
    }

    /**
     * @return array<string, string|null>
     */
    protected function generateImageDerivatives(string $absoluteOriginal, string $baseDir): array
    {
        $out = [
            'optimized_path' => null,
            'webp_path' => null,
            'small_path' => null,
            'medium_path' => null,
            'large_path' => null,
            'blur_path' => null,
        ];

        try {
            $manager = new ImageManager(new GdDriver);
            $maxW = (int) config('media.max_width', 1200);
            $jpegQ = (int) config('media.jpeg_quality', 85);
            $webpQ = (int) config('media.webp_quality', 82);

            $disk = Storage::disk('public');

            $optimized = $manager->read($absoluteOriginal);
            $optimized->scaleDown(width: $maxW);

            $optimizedRelative = $baseDir.'/optimized.jpg';
            $optimized->toJpeg(quality: $jpegQ)->save($disk->path($optimizedRelative));
            $out['optimized_path'] = $optimizedRelative;

            $optimizedAbs = $disk->path($optimizedRelative);

            try {
                $webpRel = $baseDir.'/full.webp';
                $webpImg = $manager->read($optimizedAbs);
                $webpImg->toWebp(quality: $webpQ)->save($disk->path($webpRel));
                $out['webp_path'] = $webpRel;
                $out['large_path'] = $webpRel;
            } catch (Throwable $e) {
                Log::notice('Media WebP encode failed', ['error' => $e->getMessage()]);
            }

            $widths = config('media.responsive_widths', []);

            if (isset($widths['small'])) {
                try {
                    $w = (int) $widths['small'];
                    $sm = $manager->read($optimizedAbs);
                    $sm->scaleDown(width: $w);
                    $rel = $baseDir.'/small.webp';
                    $sm->toWebp(quality: $webpQ)->save($disk->path($rel));
                    $out['small_path'] = $rel;
                } catch (Throwable $e) {
                    Log::notice('Media small WebP failed', ['error' => $e->getMessage()]);
                }
            }

            if (isset($widths['medium'])) {
                try {
                    $w = (int) $widths['medium'];
                    $md = $manager->read($optimizedAbs);
                    $md->scaleDown(width: $w);
                    $rel = $baseDir.'/medium.webp';
                    $md->toWebp(quality: $webpQ)->save($disk->path($rel));
                    $out['medium_path'] = $rel;
                } catch (Throwable $e) {
                    Log::notice('Media medium WebP failed', ['error' => $e->getMessage()]);
                }
            }

            if ($out['large_path'] === null && isset($widths['large'])) {
                try {
                    $w = (int) $widths['large'];
                    $lg = $manager->read($optimizedAbs);
                    $lg->scaleDown(width: $w);
                    $rel = $baseDir.'/large.webp';
                    $lg->toWebp(quality: $webpQ)->save($disk->path($rel));
                    $out['large_path'] = $rel;
                    if ($out['webp_path'] === null) {
                        $out['webp_path'] = $rel;
                    }
                } catch (Throwable $e) {
                    Log::notice('Media large WebP failed', ['error' => $e->getMessage()]);
                }
            }

            $blurW = (int) config('media.blur_width', 20);
            $blur = $manager->read($optimizedAbs);
            $blur->scaleDown(width: $blurW);
            $blurRel = $baseDir.'/blur.jpg';
            $blur->toJpeg(quality: 45)->save($disk->path($blurRel));
            $out['blur_path'] = $blurRel;
        } catch (Throwable $e) {
            Log::warning('Media image derivatives failed', ['error' => $e->getMessage()]);
        }

        return $out;
    }

    protected function isProcessableImage(string $mime, string $ext): bool
    {
        if ($mime === 'image/svg+xml' || $ext === 'svg') {
            return false;
        }

        return str_starts_with($mime, 'image/');
    }

    protected function resolveFileType(string $mime): string
    {
        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        return 'document';
    }
}
