<?php

namespace App\Services\Operations;

use App\Models\ServiceCategory;
use App\Models\ServiceCategorySeo;
use App\Models\SubService;
use App\Models\SubServiceSeo;

class CatalogOptimizationScorer
{
    public function __construct(
        private readonly ServiceImageSeoService $imageSeo,
    ) {}

    public function scoreAndPersist(ServiceCategory|SubService $entity): array
    {
        $result = $this->analyze($entity);

        if ($entity instanceof ServiceCategory) {
            ServiceCategorySeo::query()->updateOrCreate(
                ['service_category_id' => $entity->id],
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
        } else {
            SubServiceSeo::query()->updateOrCreate(
                ['sub_service_id' => $entity->id],
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
        }

        $entity->forceFill([
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
    public function analyze(ServiceCategory|SubService $entity): array
    {
        $entity->loadMissing(['seo', 'faqs', 'schema']);
        $recommendations = [];

        $seoScore = $this->scoreSeo($entity, $recommendations);
        $aeoScore = $this->scoreAeo($entity, $recommendations);
        $geoScore = $this->scoreGeo($entity, $recommendations);
        $schemaScore = $this->scoreSchema($entity, $recommendations);
        $contentScore = $this->scoreContent($entity, $recommendations);
        $localScore = $entity instanceof SubService ? $this->scoreSubLocal($entity, $recommendations) : $this->scoreCategoryLocal($entity, $recommendations);
        $imageScore = $this->imageSeo->scoreCatalogEntity($entity);
        $aiDiscoveryScore = (int) round(($seoScore + $aeoScore + $geoScore + $schemaScore + $contentScore + $localScore + $imageScore) / 7);

        if ($aiDiscoveryScore < 50) {
            $recommendations[] = [
                'area' => 'ai_discovery',
                'message' => __('Improve overall catalog signals to strengthen AI discovery.'),
                'priority' => 'medium',
            ];
        }

        return [
            'seo_score' => $seoScore,
            'aeo_score' => $aeoScore,
            'geo_score' => $geoScore,
            'schema_health_score' => $schemaScore,
            'content_quality_score' => $contentScore,
            'local_seo_score' => $localScore,
            'ai_discovery_score' => min(100, $aiDiscoveryScore),
            'image_seo_score' => $imageScore,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * @param  list<array{area: string, message: string, priority: string}>  $recommendations
     */
    private function scoreSeo(ServiceCategory|SubService $entity, array &$recommendations): int
    {
        $seo = $entity->seo;
        $score = 0;

        if (filled($seo?->meta_title)) {
            $score += 25;
        } else {
            $recommendations[] = ['area' => 'seo', 'message' => __('Add a meta title.'), 'priority' => 'high'];
        }

        if (filled($seo?->meta_description)) {
            $score += 25;
        } else {
            $recommendations[] = ['area' => 'seo', 'message' => __('Add a meta description.'), 'priority' => 'high'];
        }

        if (filled($seo?->h1)) {
            $score += 15;
        }

        if (is_array($seo?->focus_keywords) && $seo->focus_keywords !== []) {
            $score += 20;
        }

        if (filled($seo?->canonical_url)) {
            $score += 15;
        }

        return min(100, $score);
    }

    /**
     * @param  list<array{area: string, message: string, priority: string}>  $recommendations
     */
    private function scoreAeo(ServiceCategory|SubService $entity, array &$recommendations): int
    {
        $seo = $entity->seo;
        $score = 0;

        if (filled($seo?->ai_context)) {
            $score += 40;
        } else {
            $recommendations[] = ['area' => 'aeo', 'message' => __('Add AI context for answer engines.'), 'priority' => 'medium'];
        }

        if (filled($seo?->search_intent)) {
            $score += 20;
        }

        if (is_array($entity->ai_keywords) && $entity->ai_keywords !== []) {
            $score += 20;
        }

        if ($entity->faqs()->count() > 0) {
            $score += 20;
        } else {
            $recommendations[] = ['area' => 'aeo', 'message' => __('Add at least one FAQ.'), 'priority' => 'medium'];
        }

        return min(100, $score);
    }

    /**
     * @param  list<array{area: string, message: string, priority: string}>  $recommendations
     */
    private function scoreGeo(ServiceCategory|SubService $entity, array &$recommendations): int
    {
        $seo = $entity->seo;
        $score = 0;

        if (is_array($seo?->geo_entities) && $seo->geo_entities !== []) {
            $score += 40;
        }

        if ($entity instanceof ServiceCategory && is_array($seo?->geo_signals) && $seo->geo_signals !== []) {
            $score += 40;
        }

        if ($entity instanceof SubService) {
            $entity->loadMissing('service.pincodes');
            if ($entity->service?->pincodes?->isNotEmpty()) {
                $score += 40;
            }
        }

        if ($score < 40) {
            $recommendations[] = ['area' => 'geo', 'message' => __('Add GEO entities or location signals.'), 'priority' => 'medium'];
        }

        return min(100, $score);
    }

    /**
     * @param  list<array{area: string, message: string, priority: string}>  $recommendations
     */
    private function scoreSchema(ServiceCategory|SubService $entity, array &$recommendations): int
    {
        $schema = $entity->schema;
        if ($schema === null || ! is_array($schema->schema_json) || $schema->schema_json === []) {
            $recommendations[] = ['area' => 'schema', 'message' => __('Add structured data JSON-LD.'), 'priority' => 'medium'];

            return 20;
        }

        return filled($schema->schema_type) ? 85 : 60;
    }

    /**
     * @param  list<array{area: string, message: string, priority: string}>  $recommendations
     */
    private function scoreContent(ServiceCategory|SubService $entity, array &$recommendations): int
    {
        $score = 0;

        if (filled($entity->short_summary)) {
            $score += 20;
        }

        if (filled($entity->description) && strlen((string) $entity->description) > 120) {
            $score += 30;
        } else {
            $recommendations[] = ['area' => 'content', 'message' => __('Expand the description.'), 'priority' => 'medium'];
        }

        if (is_array($entity->key_benefits) && $entity->key_benefits !== []) {
            $score += 15;
        }

        if (filled($entity->featured_image) || filled($entity->featured_media_id)) {
            $score += 20;
        }

        if (is_array($entity->procedures) && $entity->procedures !== []) {
            $score += 15;
        }

        return min(100, $score);
    }

    /**
     * @param  list<array{area: string, message: string, priority: string}>  $recommendations
     */
    private function scoreCategoryLocal(ServiceCategory $category, array &$recommendations): int
    {
        $category->loadCount('services');
        if ($category->services_count > 0) {
            return 70;
        }

        $recommendations[] = ['area' => 'local', 'message' => __('Assign services to this category.'), 'priority' => 'low'];

        return 25;
    }

    /**
     * @param  list<array{area: string, message: string, priority: string}>  $recommendations
     */
    private function scoreSubLocal(SubService $subService, array &$recommendations): int
    {
        $subService->loadMissing('service.pincodes');
        $count = $subService->service?->pincodes?->count() ?? 0;
        if ($count > 0) {
            return min(100, 40 + min(60, $count * 5));
        }

        $recommendations[] = ['area' => 'local', 'message' => __('Parent service has no pincodes — add GEO coverage on the parent service.'), 'priority' => 'low'];

        return 20;
    }
}
