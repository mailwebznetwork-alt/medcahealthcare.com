<?php

namespace App\Services\Operations;

use App\Models\ServiceCategory;
use App\Services\Seo\CategoryJsonLdBuilder;
use App\Services\Seo\LocalityContextResolver;

/**
 * Upgrades categories from taxonomy to first-class discovery entities (DB-first).
 */
class CategoryDiscoveryEngine
{
    public function __construct(
        private readonly CategoryJsonLdBuilder $jsonLdBuilder,
        private readonly LocalityContextResolver $locality,
    ) {}

    public function sync(ServiceCategory $category, bool $fillEmptyOnly = true): void
    {
        $category->loadMissing(['seo', 'faqs', 'schema']);

        $plain = trim((string) ($category->description ?? ''));
        $city = $this->locality->primaryCity();
        $area = $this->locality->primaryAreaLabel();

        $seoPayload = [];
        $seo = $category->seo;

        if (! $fillEmptyOnly || blank($seo?->meta_title)) {
            $seoPayload['meta_title'] = mb_substr($category->name, 0, 255);
        }
        if (! $fillEmptyOnly || blank($seo?->meta_description)) {
            $seoPayload['meta_description'] = mb_substr(
                $plain !== '' ? $plain : $category->name.' — '.__('Medical Laboratory services'),
                0,
                500
            );
        }
        if (! $fillEmptyOnly || blank($seo?->canonical_url)) {
            $seoPayload['canonical_url'] = $category->publicUrl();
        }
        if (! $fillEmptyOnly || blank($seo?->og_title)) {
            $seoPayload['og_title'] = mb_substr($category->name, 0, 255);
        }
        if (! $fillEmptyOnly || blank($seo?->og_description)) {
            $seoPayload['og_description'] = $seoPayload['meta_description'] ?? $seo?->meta_description;
        }
        if (! $fillEmptyOnly || blank($seo?->twitter_card)) {
            $seoPayload['twitter_card'] = 'summary_large_image';
        }
        if (! $fillEmptyOnly || blank($seo?->aeo_question)) {
            $seoPayload['aeo_question'] = __('What services are included in :category?', ['category' => $category->name]);
        }
        if (! $fillEmptyOnly || blank($seo?->aeo_answer)) {
            $seoPayload['aeo_answer'] = mb_substr($plain !== '' ? $plain : $category->name, 0, 600);
        }
        if (! $fillEmptyOnly || blank($seo?->ai_context)) {
            $seoPayload['ai_context'] = mb_substr($plain !== '' ? $plain : $category->name, 0, 800);
        }

        $geoSignals = array_filter([
            'primary_city' => $city,
            'primary_area' => $area,
            'coverage_text' => $this->locality->coverageDisplayText(),
        ]);
        if ($geoSignals !== [] && (! $fillEmptyOnly || blank($seo?->geo_signals))) {
            $seoPayload['geo_signals'] = $geoSignals;
        }

        if (! $fillEmptyOnly || blank($seo?->geo_score) || (int) ($seo?->geo_score ?? 0) === 0) {
            $seoPayload['geo_score'] = $geoSignals !== [] ? 60 : 20;
        }
        if (! $fillEmptyOnly || blank($seo?->aeo_score) || (int) ($seo?->aeo_score ?? 0) === 0) {
            $seoPayload['aeo_score'] = $category->faqs->isNotEmpty() ? 70 : 40;
        }
        if (! $fillEmptyOnly || blank($seo?->ai_discovery_score) || (int) ($seo?->ai_discovery_score ?? 0) === 0) {
            $seoPayload['ai_discovery_score'] = filled($seoPayload['meta_description'] ?? $seo?->meta_description) ? 65 : 35;
        }

        if ($seoPayload !== []) {
            $category->seo()->updateOrCreate(['service_category_id' => $category->id], $seoPayload);
        }

        $graph = $this->jsonLdBuilder->buildGraph($category->fresh(['seo', 'faqs', 'services']));
        $category->schema()->updateOrCreate(
            ['service_category_id' => $category->id],
            [
                'schema_type' => 'CategoryDiscoveryGraph',
                'schema_json' => $graph,
            ]
        );
    }
}
