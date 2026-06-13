<?php

namespace App\Services\Seo;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;

/**
 * Hreflang readiness — English canonical now; Kannada/Hindi URLs scaffolded for future locales.
 */
class HreflangGenerator
{
    /**
     * @return array<string, string>|null
     */
    public function forCanonicalUrl(string $canonicalUrl, ?array $pageHreflang = null): ?array
    {
        if ($pageHreflang !== null && $pageHreflang !== []) {
            return $pageHreflang;
        }

        $canonical = trim($canonicalUrl);
        if ($canonical === '') {
            return null;
        }

        $locales = config('medca.hreflang_locales', []);
        if ($locales === []) {
            return ['en' => $canonical, 'x-default' => $canonical];
        }

        $map = [];
        foreach ($locales as $locale => $pathPrefix) {
            if ($locale === 'en' || $pathPrefix === null || $pathPrefix === '') {
                $map[$locale] = $canonical;
            } else {
                $base = rtrim((string) config('app.url'), '/');
                $path = parse_url($canonical, PHP_URL_PATH) ?? '/';
                $map[$locale] = $base.'/'.trim((string) $pathPrefix, '/').$path;
            }
        }

        $map['x-default'] = $map['en'] ?? $canonical;

        return $map;
    }

    public function forService(Service $service): ?array
    {
        return $this->forCanonicalUrl($service->publicUrl());
    }

    public function forCategory(ServiceCategory $category): ?array
    {
        return $this->forCanonicalUrl($category->publicUrl());
    }

    public function forLocationPage(ServiceLocationPage $mapping): ?array
    {
        return $this->forCanonicalUrl($mapping->publicUrl());
    }

    public function forSubService(SubService $sub): ?array
    {
        return $this->forCanonicalUrl($sub->publicUrl());
    }
}
