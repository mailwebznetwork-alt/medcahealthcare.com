<?php

namespace App\Services\Operations;

use App\Models\Service;

class ServiceOptimizationScorer
{
    public function __construct(
        private readonly ServiceImageSeoService $imageSeo,
    ) {}

    /**
     * @return array{
     *     seo_score: int,
     *     aeo_score: int,
     *     geo_score: int,
     *     schema_health_score: int,
     *     content_quality_score: int,
     *     local_seo_score: int,
     *     ai_discovery_score: int,
     *     image_seo_score: int,
     *     recommendations: list<array{area: string, message: string, priority: string}>
     * }
     */
    public function analyze(Service $service): array
    {
        $service->loadMissing(['seo', 'faqs', 'schema', 'pincodes', 'locationPages']);

        $recommendations = [];

        $seoScore = $this->scoreSeo($service, $recommendations);
        $aeoScore = $this->scoreAeo($service, $recommendations);
        $geoScore = $this->scoreGeo($service, $recommendations);
        $schemaScore = $this->scoreSchema($service, $recommendations);
        $contentScore = $this->scoreContent($service, $recommendations);
        $localScore = $this->scoreLocal($service, $recommendations);
        $imageScore = $this->imageSeo->score($service);
        $aiDiscoveryScore = $this->scoreAiDiscovery($service, $seoScore, $aeoScore, $geoScore, $schemaScore, $contentScore, $localScore, $imageScore, $recommendations);

        return [
            'seo_score' => $seoScore,
            'aeo_score' => $aeoScore,
            'geo_score' => $geoScore,
            'schema_health_score' => $schemaScore,
            'content_quality_score' => $contentScore,
            'local_seo_score' => $localScore,
            'image_seo_score' => $imageScore,
            'ai_discovery_score' => $aiDiscoveryScore,
            'recommendations' => $recommendations,
        ];
    }

    public function scoreAndPersist(Service $service): array
    {
        $result = $this->analyze($service);

        $service->seo()->updateOrCreate(
            ['service_id' => $service->id],
            [
                'seo_score' => $result['seo_score'],
                'aeo_score' => $result['aeo_score'],
                'geo_score' => $result['geo_score'],
                'schema_health_score' => $result['schema_health_score'],
                'content_quality_score' => $result['content_quality_score'],
                'local_seo_score' => $result['local_seo_score'],
                'ai_discovery_score' => $result['ai_discovery_score'],
                'image_seo_score' => $result['image_seo_score'],
                'seo_recommendations' => $result['recommendations'],
            ]
        );

        $service->forceFill([
            'optimization_snapshot' => [
                'scored_at' => now()->toIso8601String(),
                'scores' => [
                    'seo' => $result['seo_score'],
                    'aeo' => $result['aeo_score'],
                    'geo' => $result['geo_score'],
                    'schema' => $result['schema_health_score'],
                    'content' => $result['content_quality_score'],
                    'local' => $result['local_seo_score'],
                    'image' => $result['image_seo_score'],
                    'ai_discovery' => $result['ai_discovery_score'],
                ],
                'recommendations' => $result['recommendations'],
            ],
        ])->saveQuietly();

        return $result;
    }

    /**
     * @param  list<array{area: string, message: string, priority: string}>  $recommendations
     */
    private function scoreSeo(Service $service, array &$recommendations): int
    {
        $score = 0;
        $seo = $service->seo;

        if (filled($seo?->meta_title)) {
            $score += 25;
        } else {
            $recommendations[] = $this->rec('seo', __('Add an SEO title (meta title).'), 'high');
        }

        if (filled($seo?->meta_description) && mb_strlen((string) $seo->meta_description) >= 120) {
            $score += 25;
        } else {
            $recommendations[] = $this->rec('seo', __('Write a meta description of at least 120 characters.'), 'high');
        }

        $focus = is_array($seo?->focus_keywords) ? count($seo->focus_keywords) : 0;
        if ($focus >= 1) {
            $score += 20;
        } else {
            $recommendations[] = $this->rec('seo', __('Set a focus keyword for this service.'), 'medium');
        }

        if (filled($seo?->h1)) {
            $score += 15;
        } else {
            $recommendations[] = $this->rec('seo', __('Set the primary H1 in the SEO tab.'), 'medium');
        }

        if (filled($seo?->canonical_url)) {
            $score += 15;
        } else {
            $recommendations[] = $this->rec('seo', __('Confirm the canonical URL points to /services/{code}.'), 'low');
        }

        return min(100, $score);
    }

    /**
     * @param  list<array{area: string, message: string, priority: string}>  $recommendations
     */
    private function scoreAeo(Service $service, array &$recommendations): int
    {
        $score = 0;

        if (filled($service->seo?->ai_context)) {
            $score += 30;
        } else {
            $recommendations[] = $this->rec('aeo', __('Add AI context for answer engines (AEO tab).'), 'high');
        }

        if (filled($service->ai_summary)) {
            $score += 25;
        } else {
            $recommendations[] = $this->rec('aeo', __('Add an AI summary (GEO / discovery).'), 'medium');
        }

        $faqCount = $service->faqs->filter(fn ($f) => filled($f->question) && filled($f->answer))->count();
        if ($faqCount >= 3) {
            $score += 35;
        } elseif ($faqCount >= 1) {
            $score += 20;
            $recommendations[] = $this->rec('aeo', __('Add at least 3 FAQs for FAQPage schema.'), 'medium');
        } else {
            $recommendations[] = $this->rec('aeo', __('Build FAQs in the FAQ tab for AI overviews.'), 'high');
        }

        if (filled($service->seo?->search_intent)) {
            $score += 10;
        }

        return min(100, $score);
    }

    /**
     * @param  list<array{area: string, message: string, priority: string}>  $recommendations
     */
    private function scoreGeo(Service $service, array &$recommendations): int
    {
        $score = 0;

        if (filled($service->ai_summary)) {
            $score += 25;
        }

        $entities = is_array($service->seo?->entity_tags) ? count($service->seo->entity_tags) : 0;
        if ($entities >= 2) {
            $score += 25;
        } else {
            $recommendations[] = $this->rec('geo', __('Add entity tags (conditions, audience) for generative search.'), 'medium');
        }

        if (is_array($service->ai_keywords) && count($service->ai_keywords) >= 2) {
            $score += 25;
        } else {
            $recommendations[] = $this->rec('geo', __('Add AI discovery keywords on the Content tab.'), 'medium');
        }

        if (filled($service->short_summary)) {
            $score += 25;
        }

        return min(100, $score);
    }

    /**
     * @param  list<array{area: string, message: string, priority: string}>  $recommendations
     */
    private function scoreSchema(Service $service, array &$recommendations): int
    {
        $schema = $service->schema?->schema_json;
        if (! is_array($schema) || $schema === []) {
            $recommendations[] = $this->rec('schema', __('Schema will auto-generate on save — re-save if empty.'), 'medium');

            return 40;
        }

        $graph = $schema['@graph'] ?? null;
        if (! is_array($graph) || count($graph) < 3) {
            $recommendations[] = $this->rec('schema', __('Schema graph is incomplete — sync from Services.'), 'high');

            return 50;
        }

        return min(100, 40 + (count($graph) * 10));
    }

    /**
     * @param  list<array{area: string, message: string, priority: string}>  $recommendations
     */
    private function scoreContent(Service $service, array &$recommendations): int
    {
        $score = 0;

        if (filled($service->short_summary)) {
            $score += 20;
        }
        if (filled($service->description) && mb_strlen(strip_tags((string) $service->description)) >= 200) {
            $score += 25;
        } else {
            $recommendations[] = $this->rec('content', __('Expand the full description (200+ characters).'), 'medium');
        }

        if (is_array($service->key_benefits) && count($service->key_benefits) >= 2) {
            $score += 20;
        } else {
            $recommendations[] = $this->rec('content', __('List key benefits (2+ items).'), 'medium');
        }

        if (filled($service->featured_image)) {
            $score += 20;
        } else {
            $recommendations[] = $this->rec('content', __('Upload a featured image.'), 'high');
        }

        if (filled($service->image_alt)) {
            $score += 15;
        } else {
            $recommendations[] = $this->rec('content', __('Set featured image alt text.'), 'medium');
        }

        return min(100, $score);
    }

    /**
     * @param  list<array{area: string, message: string, priority: string}>  $recommendations
     */
    private function scoreLocal(Service $service, array &$recommendations): int
    {
        $pinCount = $service->pincodes->count();
        if ($pinCount === 0) {
            $recommendations[] = $this->rec('local', __('Assign serviceable pincodes on the GEO tab.'), 'high');

            return 10;
        }

        $score = min(70, 20 + ($pinCount * 5));

        if ($service->locationPages()->count() < $pinCount) {
            $recommendations[] = $this->rec('local', __('Location pages will sync on save — run Sync if missing.'), 'low');
        } else {
            $score += 30;
        }

        return min(100, $score);
    }

    /**
     * @param  list<array{area: string, message: string, priority: string}>  $recommendations
     */
    private function scoreAiDiscovery(
        Service $service,
        int $seo,
        int $aeo,
        int $geo,
        int $schema,
        int $content,
        int $local,
        int $image,
        array &$recommendations,
    ): int {
        $faqCount = $service->faqs->filter(fn ($f) => filled($f->question))->count();
        $entityCount = is_array($service->seo?->entity_tags) ? count($service->seo->entity_tags) : 0;
        $hasLinks = is_array($service->internal_links_snapshot)
            && (
                count($service->internal_links_snapshot['related_services'] ?? []) > 0
                || count($service->internal_links_snapshot['related_locations'] ?? []) > 0
            );

        $score = (int) round(($seo + $aeo + $geo + $schema + $content + $local + $image) / 7);

        if (! filled($service->ai_summary)) {
            $recommendations[] = $this->rec('ai_discovery', __('Add AI summary for discovery surfaces.'), 'high');
            $score -= 10;
        }
        if ($faqCount < 3) {
            $recommendations[] = $this->rec('ai_discovery', __('Add more FAQs for AI overviews.'), 'medium');
        }
        if ($entityCount < 2) {
            $recommendations[] = $this->rec('ai_discovery', __('Expand entity tags for knowledge graph coverage.'), 'medium');
        }
        if (! $hasLinks) {
            $recommendations[] = $this->rec('ai_discovery', __('Run master sync to build internal links.'), 'low');
        }

        return max(0, min(100, $score));
    }

    /**
     * @return array{area: string, message: string, priority: string}
     */
    private function rec(string $area, string $message, string $priority): array
    {
        return [
            'area' => $area,
            'message' => $message,
            'priority' => $priority,
        ];
    }
}
