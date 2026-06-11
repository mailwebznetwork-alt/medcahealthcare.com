<?php

namespace App\Services\Growth;

use Illuminate\Support\Facades\Storage;

class SeoSitemapFileGenerator
{
    public function __construct(
        private readonly SeoService $seoService,
    ) {}

    public function regenerateAll(): void
    {
        $disk = Storage::disk(config('sitemap.cache_disk', 'local'));
        $dir = trim(config('sitemap.cache_directory', 'sitemaps'), '/');

        $disk->put("{$dir}/sitemap.xml", $this->seoService->generateSitemapIndex());

        $segments = $this->segmentGenerators();
        foreach ($segments as $filename => $generator) {
            $disk->put("{$dir}/{$filename}", $generator());
        }
    }

    public function readCached(string $filename): ?string
    {
        if (! config('sitemap.cache_enabled', true)) {
            return null;
        }

        $disk = Storage::disk(config('sitemap.cache_disk', 'local'));
        $path = trim(config('sitemap.cache_directory', 'sitemaps'), '/').'/'.$filename;

        if (! $disk->exists($path)) {
            return null;
        }

        return $disk->get($path);
    }

    /**
     * @return array<string, callable(): string>
     */
    private function segmentGenerators(): array
    {
        $generators = [
            'sitemap-static-pages.xml' => fn (): string => $this->seoService->generateStaticPagesSitemapXml(),
            'sitemap-blogs.xml' => fn (): string => $this->seoService->generateBlogsSitemapXml(),
            'sitemap-services.xml' => fn (): string => $this->seoService->generateServiceDetailsSitemapXml(),
            'sitemap-categories.xml' => fn (): string => $this->seoService->generateCategoriesSitemapXml(),
            'sitemap-subservices.xml' => fn (): string => $this->seoService->generateSubservicesSitemapXml(),
            'sitemap-images.xml' => fn (): string => $this->seoService->generateImagesSitemapXml(),
        ];

        if (config('sitemap.paginated_enabled', true)) {
            $chunkCount = $this->seoService->locationSitemapChunkCount();
            for ($i = 1; $i <= $chunkCount; $i++) {
                $chunk = $i;
                $generators[sprintf('sitemap-locations-%03d.xml', $chunk)] = fn (): string => $this->seoService->generateLocationChunkSitemapXml($chunk);
            }
        } else {
            $generators['sitemap-pages.xml'] = fn (): string => $this->seoService->generatePagesSitemapXml();
            $generators['sitemap-services.xml'] = fn (): string => $this->seoService->generateServicesSitemapXml();
        }

        return $generators;
    }
}
