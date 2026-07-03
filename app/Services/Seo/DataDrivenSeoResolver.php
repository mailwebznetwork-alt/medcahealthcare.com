<?php

namespace App\Services\Seo;

use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use App\Support\ServicePageOverrides;

/**
 * Runtime SEO from business data + rules. No page-level storage for generated pages.
 */
class DataDrivenSeoResolver
{
    /**
     * @return array{title: string, meta_title: string, meta_description: string|null, h1: string|null, canonical: string|null}|null
     */
    public function resolve(
        ?Page $page = null,
        ?Service $service = null,
        ?ServiceCategory $category = null,
        ?ServiceLocationPage $mapping = null,
        ?SubService $subService = null,
    ): ?array {
        if (! config('seo_rules.enabled', true)) {
            return null;
        }

        if ($mapping !== null && $service !== null && $mapping->pincode instanceof PinCode) {
            return $this->fromTemplate('location', [
                'service_title' => $service->title,
                'service_summary' => $service->short_summary ?? '',
                'pincode' => $mapping->pincode->pincode,
                'pincode_area' => $mapping->pincode->area_name ?: $mapping->pincode->locality ?: $mapping->pincode->city ?: $mapping->pincode->pincode,
                'brand' => config('seo_rules.brand_name'),
            ], $page?->publicUrl());
        }

        if ($subService !== null) {
            $subService->loadMissing(['seo', 'service']);

            return $this->fromTemplate('service', [
                'service_title' => $subService->title,
                'service_summary' => $subService->seo?->meta_description ?? $subService->short_summary ?? '',
                'brand' => config('seo_rules.brand_name'),
            ], $subService->publicUrl());
        }

        if ($service !== null) {
            $service->loadMissing('seo');

            $resolved = $this->fromTemplate('service', [
                'service_title' => $service->title,
                'service_summary' => $service->seo?->meta_description ?? $service->short_summary ?? '',
                'brand' => config('seo_rules.brand_name'),
            ], $page?->publicUrl() ?? url('/services/'.$service->service_code));

            return $this->preferExplicitSeo($resolved, $service->seo);
        }

        if ($category !== null) {
            $category->loadMissing('seo');

            $resolved = $this->fromTemplate('category', [
                'category_name' => $category->name,
                'brand' => config('seo_rules.brand_name'),
            ], url('/service-categories/'.$category->code));

            return $this->preferExplicitSeo($resolved, $category->seo);
        }

        if ($page !== null && $page->page_source !== 'generated') {
            $plain = strip_tags((string) $page->content);

            return $this->fromTemplate('static_page', [
                'page_title' => $page->title,
                'page_excerpt' => mb_substr(trim($plain), 0, 160),
                'brand' => config('seo_rules.brand_name'),
            ], $page->publicUrl());
        }

        return null;
    }

    public static function shouldUseForPage(?Page $page): bool
    {
        return config('seo_rules.enabled', true)
            && $page !== null
            && ! ServicePageOverrides::seoOverride($page);
    }

    /**
     * If the entity has explicit SEO values (meta_title / meta_description / h1),
     * prefer them over generated templates so hand-crafted keyword targeting wins.
     *
     * @param  array{title: string, meta_title: string, meta_description: string|null, h1: string|null, canonical: string|null}  $resolved
     * @return array{title: string, meta_title: string, meta_description: string|null, h1: string|null, canonical: string|null}
     */
    private function preferExplicitSeo(array $resolved, mixed $seo): array
    {
        if ($seo === null) {
            return $resolved;
        }

        $metaTitle = filled($seo->meta_title ?? null) ? (string) $seo->meta_title : $resolved['meta_title'];
        $metaDescription = filled($seo->meta_description ?? null) ? (string) $seo->meta_description : $resolved['meta_description'];
        $h1 = filled($seo->h1 ?? null) ? (string) $seo->h1 : $resolved['h1'];

        return [
            'title' => $h1 ?? $metaTitle,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'h1' => $h1,
            'canonical' => $resolved['canonical'],
        ];
    }

    /**
     * @param  array<string, string>  $tokens
     * @return array{title: string, meta_title: string, meta_description: string|null, h1: string|null, canonical: string|null}
     */
    private function fromTemplate(string $key, array $tokens, ?string $canonical = null): array
    {
        $templates = config("seo_rules.templates.{$key}", []);
        $replace = fn (?string $template): ?string => $template === null
            ? null
            : mb_substr(trim(preg_replace_callback('/\{([a-z_]+)\}/', fn ($m) => $tokens[$m[1]] ?? '', $template) ?? ''), 0, 320);

        $metaTitle = $replace($templates['meta_title'] ?? null) ?? ($tokens['service_title'] ?? $tokens['page_title'] ?? config('seo_rules.brand_name'));
        $h1 = $replace($templates['h1'] ?? null);

        return [
            'title' => $h1 ?? $metaTitle,
            'meta_title' => $metaTitle,
            'meta_description' => $replace($templates['meta_description'] ?? null),
            'h1' => $h1,
            'canonical' => $canonical,
        ];
    }
}
