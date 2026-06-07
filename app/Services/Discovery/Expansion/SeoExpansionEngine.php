<?php

namespace App\Services\Discovery\Expansion;

use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;

/**
 * Dynamic SEO field generation — single write path per entity page.
 */
class SeoExpansionEngine
{
    /**
     * @return array<string, mixed>
     */
    public function forCategoryPage(ServiceCategory $category, Page $page): array
    {
        $category->loadMissing('seo');

        return array_filter([
            'meta_title' => $category->seo?->meta_title ?: $category->name,
            'meta_description' => $category->seo?->meta_description,
            'canonical_url' => $category->seo?->canonical_url ?: $category->publicUrl(),
            'robots_meta' => ($category->seo?->robots_index ?? true) ? 'index,follow' : 'noindex,follow',
            'og_title' => $category->seo?->og_title ?: $category->name,
            'og_description' => $category->seo?->og_description ?: $category->seo?->meta_description,
            'twitter_card' => $category->seo?->twitter_card ?: 'summary_large_image',
            'h1' => $category->seo?->meta_title ?: $category->name,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forServicePage(Service $service, Page $page): array
    {
        $service->loadMissing('seo');

        return array_filter([
            'meta_title' => $service->seo?->meta_title ?: $service->title,
            'meta_description' => $service->seo?->meta_description,
            'canonical_url' => $service->seo?->canonical_url ?: $service->publicUrl(),
            'robots_meta' => ($service->seo?->robots_index ?? true) ? 'index,follow' : 'noindex,follow',
            'og_title' => $service->seo?->og_title,
            'og_description' => $service->seo?->og_description,
            'twitter_card' => $service->seo?->twitter_card,
            'h1' => $service->seo?->h1 ?: $service->title,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forSubServicePage(SubService $sub, Page $page): array
    {
        $sub->loadMissing(['seo', 'service']);
        $title = $sub->title;
        $parent = $sub->service?->title;

        return array_filter([
            'meta_title' => $sub->seo?->meta_title ?: ($parent ? $title.' — '.$parent : $title),
            'meta_description' => $sub->seo?->meta_description ?: $sub->short_summary ?: $sub->description,
            'canonical_url' => $sub->seo?->canonical_url ?: $this->subServicePublicUrl($sub),
            'robots_meta' => ($sub->seo?->robots_index ?? true) ? 'index,follow' : 'noindex,follow',
            'h1' => $sub->seo?->h1 ?: $title,
        ]);
    }

    public function subServicePublicUrl(SubService $sub): string
    {
        $sub->loadMissing('service');
        $pattern = (string) config('phase2_discovery.sub_service_public_path_pattern', '/services/{code}/sub/{sub}');

        return url(str_replace(
            ['{code}', '{sub}'],
            [$sub->service?->service_code ?? '', $sub->sub_service_code],
            $pattern
        ));
    }
}
