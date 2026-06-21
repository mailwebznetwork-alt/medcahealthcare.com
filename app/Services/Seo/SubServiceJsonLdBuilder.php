<?php

namespace App\Services\Seo;

use App\Models\SubService;

class SubServiceJsonLdBuilder
{
    public function __construct(
        private readonly GeoBusinessContextResolver $geoContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildGraph(SubService $sub): array
    {
        $sub->loadMissing(['seo', 'faqs', 'schema', 'service.seo', 'service.categories']);
        $service = $sub->service;
        $canonical = app(\App\Services\Discovery\Expansion\SeoExpansionEngine::class)->subServicePublicUrl($sub);
        $ctx = $this->geoContext->resolve($service, null);

        $graph = [
            [
                '@type' => 'Organization',
                '@id' => $ctx['site_url'].'/#organization',
                'name' => $ctx['brand'],
                'url' => $ctx['site_url'],
            ],
            [
                '@type' => 'Service',
                '@id' => $canonical.'#service',
                'name' => $sub->title,
                'description' => $sub->short_summary ?: $sub->description,
                'url' => $canonical,
                'provider' => ['@id' => $ctx['site_url'].'/#organization'],
                'isPartOf' => $service ? ['@id' => $service->publicUrl().'#service'] : null,
            ],
        ];

        $faqs = $sub->faqs
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

        $graph[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $canonical.'#breadcrumb',
            'itemListElement' => array_values(array_filter([
                $service ? ['@type' => 'ListItem', 'position' => 1, 'name' => __('Services'), 'item' => url('/services-catalog')] : null,
                $service ? ['@type' => 'ListItem', 'position'  => 2, 'name' => $service->title, 'item' => $service->publicUrl()] : null,
                ['@type' => 'ListItem', 'position' => $service ? 3 : 1, 'name' => $sub->title, 'item' => $canonical],
            ])),
        ];

        return [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter($graph)),
        ];
    }
}
