<?php

namespace App\Services\Seo;

use App\Models\ServiceCategory;

/**
 * Database-first JSON-LD for discoverable category entities.
 */
class CategoryJsonLdBuilder
{
    public function __construct(
        private readonly GeoBusinessContextResolver $geoContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildGraph(ServiceCategory $category): array
    {
        $category->loadMissing(['seo', 'faqs', 'services' => fn ($q) => $q->publicListing()->limit(12)]);
        $canonical = $category->seo?->canonical_url ?: $category->publicUrl();
        $ctx = $this->geoContext->resolve(null, null);

        $graph = [
            [
                '@type' => 'CollectionPage',
                '@id' => $canonical.'#category',
                'name' => $category->name,
                'description' => $category->seo?->meta_description ?: $category->description,
                'url' => $canonical,
            ],
        ];

        $serviceItems = $category->services
            ->map(fn ($service): array => [
                '@type' => 'ListItem',
                'position' => $service->sort_order,
                'name' => $service->title,
                'url' => $service->publicUrl(),
            ])
            ->values()
            ->all();

        if ($serviceItems !== []) {
            $graph[] = [
                '@type' => 'ItemList',
                '@id' => $canonical.'#services',
                'name' => $category->name.' '.__('Services'),
                'itemListElement' => $serviceItems,
            ];
        }

        $faqs = $category->faqs
            ->map(fn ($faq): array => [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->answer],
            ])
            ->values()
            ->all();

        if ($faqs !== []) {
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => $canonical.'#faq',
                'mainEntity' => $faqs,
            ];
        }

        if (filled($ctx['geo_coordinates'])) {
            $graph[0]['geo'] = $ctx['geo_coordinates'];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }
}
