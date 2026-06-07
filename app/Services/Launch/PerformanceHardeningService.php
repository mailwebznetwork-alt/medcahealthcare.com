<?php

namespace App\Services\Launch;

use App\Models\Media;

/**
 * Audits performance hardening without modifying rendering architecture.
 */
class PerformanceHardeningService
{
    /**
     * @return array<string, mixed>
     */
    public function audit(): array
    {
        $mediaTotal = Media::count();
        $withWebp = Media::query()->whereNotNull('webp_path')->orWhere('mime_type', 'like', '%webp%')->count();
        $lazyLoadingComponent = file_exists(resource_path('views/components/public/responsive-media.blade.php'));

        return [
            'webp_pipeline' => class_exists(\App\Services\Media\MediaUploadProcessor::class),
            'media_with_webp' => $withWebp,
            'media_total' => $mediaTotal,
            'responsive_media_component' => $lazyLoadingComponent,
            'lazy_loading' => $lazyLoadingComponent,
            'avif_enabled' => (bool) config('media.generate_avif', false),
            'cache_layers' => [
                'global_content' => 300,
                'marketing_analytics' => 900,
                'theme_config' => 'config-driven',
            ],
            'recommendations' => $this->recommendations($mediaTotal, $withWebp),
        ];
    }

    /**
     * @return list<string>
     */
    private function recommendations(int $total, int $webp): array
    {
        $recs = [];
        if ($total > 0 && $webp < $total) {
            $recs[] = 'Re-process legacy media uploads for WebP variants.';
        }
        if (! config('media.generate_avif', false)) {
            $recs[] = 'Enable AVIF generation when GD/Imagick supports it (MEDIA_GENERATE_AVIF=true).';
        }

        return $recs;
    }
}
