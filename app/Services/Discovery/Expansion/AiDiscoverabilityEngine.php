<?php

namespace App\Services\Discovery\Expansion;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;

/**
 * AI discoverability scoring and knowledge-graph readiness signals.
 */
class AiDiscoverabilityEngine
{
    /**
     * @return array<string, int|list<string>>
     */
    public function scoreCategory(ServiceCategory $category): array
    {
        $category->loadMissing(['seo', 'faqs', 'schema', 'services']);

        $score = 0;
        $signals = [];

        if (filled($category->seo?->meta_description)) {
            $score += 20;
            $signals[] = 'meta_description';
        }
        if ($category->faqs->isNotEmpty()) {
            $score += 25;
            $signals[] = 'faq';
        }
        if ($category->schema !== null) {
            $score += 25;
            $signals[] = 'schema';
        }
        if ($category->services->isNotEmpty()) {
            $score += 20;
            $signals[] = 'service_links';
        }
        if (filled($category->seo?->ai_context)) {
            $score += 10;
            $signals[] = 'ai_context';
        }

        return ['score' => min(100, $score), 'signals' => $signals];
    }

    /**
     * @return array<string, int|list<string>>
     */
    public function scoreService(Service $service): array
    {
        $service->loadMissing(['seo', 'faqs', 'schema', 'subServices']);

        $score = (int) ($service->seo?->ai_discovery_score ?? 0);
        $signals = [];

        if (filled($service->seo?->meta_description)) {
            $signals[] = 'meta_description';
        }
        if ($service->faqs->isNotEmpty()) {
            $signals[] = 'faq';
        }
        if ($service->schema !== null) {
            $signals[] = 'schema';
        }
        if ($service->subServices->isNotEmpty()) {
            $signals[] = 'sub_services';
        }

        if ($score === 0) {
            $score = min(100, count($signals) * 20);
        }

        return ['score' => $score, 'signals' => $signals];
    }

    /**
     * @return array<string, int|list<string>>
     */
    public function scoreSubService(SubService $sub): array
    {
        $sub->loadMissing(['seo', 'faqs', 'schema']);

        $score = 0;
        $signals = [];

        if (filled($sub->seo?->meta_description)) {
            $score += 30;
            $signals[] = 'meta_description';
        }
        if ($sub->faqs->isNotEmpty()) {
            $score += 30;
            $signals[] = 'faq';
        }
        if ($sub->schema !== null) {
            $score += 25;
            $signals[] = 'schema';
        }
        if (filled($sub->description)) {
            $score += 15;
            $signals[] = 'description';
        }

        return ['score' => min(100, $score), 'signals' => $signals];
    }
}
