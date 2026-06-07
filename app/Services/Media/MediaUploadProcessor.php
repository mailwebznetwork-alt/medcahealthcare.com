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
    public function process(UploadedFile $file, ?int $userId = null, ?string $sourceModule = null): Media
    {
        $this->validateUpload($file);

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
            'mime_type' => $mime !== '' ? $mime : null,
            'title' => null,
            'alt_text' => $fileType === 'image' ? '' : null,
            'description' => null,
            'uploaded_by' => $userId,
            'source_module' => $sourceModule,
        ];

        if ($fileType === 'image' && $this->isProcessableImage($mime, $ext)) {
            $derived = $this->generateImageDerivatives($disk->path($path), $baseDir);
            $data = array_merge($data, $derived);
        }

        $media = Media::query()->create($data);
        app(MediaImageSeoScorer::class)->persist($media);

        return $media->fresh();
    }

    /**
     * Import an existing file from disk into the centralized library.
     */
    public function importFromDiskPath(string $relativePath, ?int $userId = null, ?string $sourceModule = 'legacy'): Media
    {
        $relativePath = ltrim($relativePath, '/');
        $disk = Storage::disk('public');
        if (! $disk->exists($relativePath)) {
            throw new \InvalidArgumentException(__('File not found: :path', ['path' => $relativePath]));
        }

        $absolute = $disk->path($relativePath);
        $hash = hash_file('sha256', $absolute);
        $existing = Media::query()->where('file_hash', $hash)->first();
        if ($existing) {
            return $existing;
        }

        $uuid = (string) Str::uuid();
        $baseDir = 'media/'.$uuid;
        $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION) ?: 'bin');
        $fileName = basename($relativePath);
        $dest = $baseDir.'/original.'.$ext;
        $disk->copy($relativePath, $dest);

        $mime = mime_content_type($disk->path($dest)) ?: '';
        $fileType = $this->resolveFileType($mime);
        $data = [
            'uuid' => $uuid,
            'file_name' => $fileName,
            'file_path' => $dest,
            'file_url' => MediaPublicUrl::forPath($dest),
            'file_type' => $fileType,
            'file_size' => $disk->size($dest),
            'file_hash' => $hash,
            'legacy_path' => $relativePath,
            'mime_type' => $mime !== '' ? $mime : null,
            'title' => null,
            'alt_text' => $fileType === 'image' ? '' : null,
            'description' => null,
            'uploaded_by' => $userId,
            'source_module' => $sourceModule,
        ];

        if ($fileType === 'image' && $this->isProcessableImage($mime, $ext)) {
            $data = array_merge($data, $this->generateImageDerivatives($disk->path($dest), $baseDir));
        }

        $media = Media::query()->create($data);
        app(MediaImageSeoScorer::class)->persist($media);

        return $media->fresh();
    }

    public function validateUpload(UploadedFile $file): void
    {
        $maxKb = (int) config('media.max_upload_kb', 51200);
        if ($file->getSize() !== false && $file->getSize() > $maxKb * 1024) {
            throw new \InvalidArgumentException(__('File exceeds maximum upload size.'));
        }

        $mime = $file->getMimeType() ?? '';
        if (str_starts_with($mime, 'image/')) {
            $allowed = config('media.allowed_image_mimes', []);
            if (is_array($allowed) && $allowed !== [] && ! in_array($mime, $allowed, true)) {
                throw new \InvalidArgumentException(__('Unsupported image type.'));
            }
        }
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
            'thumbnail_path' => null,
            'avif_path' => null,
            'width' => null,
            'height' => null,
        ];

        try {
            $manager = new ImageManager(new GdDriver);
            $source = $manager->read($absoluteOriginal);
            $out['width'] = $source->width();
            $out['height'] = $source->height();

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

            $thumbW = (int) config('media.thumbnail_width', 160);
            try {
                $thumb = $manager->read($optimizedAbs);
                $thumb->scaleDown(width: $thumbW);
                $thumbRel = $baseDir.'/thumb.webp';
                $thumb->toWebp(quality: $webpQ)->save($disk->path($thumbRel));
                $out['thumbnail_path'] = $thumbRel;
            } catch (Throwable $e) {
                Log::notice('Media thumbnail WebP failed', ['error' => $e->getMessage()]);
            }

            if (config('media.generate_avif', false) && method_exists($manager->read($optimizedAbs), 'toAvif')) {
                try {
                    $avifRel = $baseDir.'/full.avif';
                    $manager->read($optimizedAbs)->toAvif(quality: 70)->save($disk->path($avifRel));
                    $out['avif_path'] = $avifRel;
                } catch (Throwable $e) {
                    Log::notice('Media AVIF encode skipped', ['error' => $e->getMessage()]);
                }
            }
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
