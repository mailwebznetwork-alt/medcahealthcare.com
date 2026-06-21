<?php

namespace App\Services\Operations;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServiceImageSeoService
{
    /**
     * @return array{alt: string, title: string, caption: string, description: string}
     */
    public function suggestFeatured(Service $service, ?string $area = null): array
    {
        $area = $area ?: app(\App\Services\Seo\LocalityContextResolver::class)->primaryAreaLabel()
            ?: config('medca.location_display', '');
        $title = $service->title;

        return [
            'alt' => __('Professional :service in :area', ['service' => $title, 'area' => $area]),
            'title' => $title,
            'caption' => __('Qualified care with :brand', ['brand' => config('medca.brand_name', 'MarkOnMinds')]),
            'description' => mb_substr(
                trim((string) ($service->short_summary ?: $service->seo?->meta_description ?: $title)),
                0,
                320
            ),
        ];
    }

    /**
     * @return array{alt: string, title: string, caption: string, description: string}
     */
    public function suggestGalleryItem(Service $service, int $index = 0): array
    {
        $base = $this->suggestFeatured($service);
        $base['alt'] = __(':service — gallery image :n', ['service' => $service->title, 'n' => $index + 1]);

        return $base;
    }

    public function score(Service $service): int
    {
        $score = 0;
        $featured = is_array($service->featured_image_meta) ? $service->featured_image_meta : [];

        if (filled($service->featured_image)) {
            $score += 25;
        }
        if (filled($featured['alt'] ?? $service->image_alt)) {
            $score += 30;
        }
        if (filled($featured['title'] ?? null)) {
            $score += 15;
        }
        if (filled($featured['description'] ?? null)) {
            $score += 15;
        }

        $galleryMeta = is_array($service->gallery_meta) ? $service->gallery_meta : [];
        $gallery = is_array($service->gallery) ? $service->gallery : [];
        if ($gallery !== []) {
            $withAlt = 0;
            foreach ($gallery as $path) {
                $meta = $galleryMeta[$path] ?? $galleryMeta[basename((string) $path)] ?? null;
                if (is_array($meta) && filled($meta['alt'] ?? null)) {
                    $withAlt++;
                }
            }
            $score += (int) min(15, ($withAlt / max(1, count($gallery))) * 15);
        }

        return min(100, $score);
    }

    public function scoreCatalogEntity(ServiceCategory|SubService $entity): int
    {
        $score = 0;
        $featured = is_array($entity->featured_image_meta) ? $entity->featured_image_meta : [];

        if (filled($entity->featured_image)) {
            $score += 25;
        }
        if (filled($featured['alt'] ?? $entity->image_alt)) {
            $score += 30;
        }
        if (filled($featured['title'] ?? null)) {
            $score += 15;
        }
        if (filled($featured['description'] ?? null)) {
            $score += 15;
        }

        $galleryMeta = is_array($entity->gallery_meta) ? $entity->gallery_meta : [];
        $gallery = is_array($entity->gallery) ? $entity->gallery : [];
        if ($gallery !== []) {
            $withAlt = 0;
            foreach ($gallery as $path) {
                $meta = $galleryMeta[$path] ?? $galleryMeta[basename((string) $path)] ?? null;
                if (is_array($meta) && filled($meta['alt'] ?? null)) {
                    $withAlt++;
                }
            }
            $score += (int) min(15, ($withAlt / max(1, count($gallery))) * 15);
        }

        return min(100, $score);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function geminiImageHints(Service $service): ?array
    {
        if (! config('services_master.gemini_suggestions', true)) {
            return null;
        }

        $apiKey = config('gemini.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        try {
            $response = Http::timeout(30)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key='.urlencode(trim($apiKey)),
                [
                    'contents' => [['parts' => [['text' => 'Return JSON only: {"featured":{"alt":"","title":"","caption":"","description":""}} for digital growth platform service: '.$service->title]]]],
                ]
            );
            if (! $response->successful()) {
                return null;
            }
            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
            if (! is_string($text)) {
                return null;
            }
            $decoded = json_decode(trim(preg_replace('/^```json\s*|\s*```$/', '', trim($text)) ?? ''), true);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            Log::warning('ServiceImageSeoService gemini', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
