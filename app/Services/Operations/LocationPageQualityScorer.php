<?php

namespace App\Services\Operations;

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;

class LocationPageQualityScorer
{
    /**
     * @return array{
     *   content_uniqueness: int,
     *   geo_readiness: int,
     *   aeo_readiness: int,
     *   local_relevance: int,
     *   composite: int,
     *   is_indexable: bool,
     *   reasons: list<string>
     * }
     */
    public function score(Service $service, PinCode $pin, ServiceLocationPage $mapping): array
    {
        $pin->loadMissing(['landmarks', 'hospitals', 'locationFaqs', 'nearbyAreas']);
        $reasons = [];

        if (! ServiceLocationMatrixPivot::isActive($service, $pin)) {
            return [
                'content_uniqueness' => 0,
                'geo_readiness' => 0,
                'aeo_readiness' => 0,
                'local_relevance' => 0,
                'composite' => 0,
                'is_indexable' => false,
                'reasons' => ['matrix_pivot_inactive'],
            ];
        }

        $contentUniqueness = 0;
        if (filled($pin->coverage_text)) {
            $contentUniqueness += 25;
        }
        if ($pin->nearbyAreas->isNotEmpty()) {
            $contentUniqueness += 20;
        }
        if ($pin->landmarks->isNotEmpty()) {
            $contentUniqueness += 20;
        }
        if ($pin->hospitals->isNotEmpty()) {
            $contentUniqueness += 15;
        }
        if ($pin->locationFaqs->count() >= 2) {
            $contentUniqueness += 20;
        }
        $contentUniqueness = min(100, $contentUniqueness);

        $geoReadiness = 0;
        if (filled($pin->area_name) && filled($pin->city)) {
            $geoReadiness += 30;
        }
        if (filled($pin->state)) {
            $geoReadiness += 10;
        }
        if (filled($pin->coverage_text)) {
            $geoReadiness += 25;
        }
        if ($pin->geo_page_ready) {
            $geoReadiness += 15;
        }
        if ($pin->landmarks->isNotEmpty() || $pin->hospitals->isNotEmpty()) {
            $geoReadiness += 20;
        }
        $geoReadiness = min(100, $geoReadiness);

        $aeoReadiness = 0;
        $faqCount = $pin->locationFaqs->count();
        if ($faqCount >= 3) {
            $aeoReadiness += 50;
        } elseif ($faqCount >= 1) {
            $aeoReadiness += 25;
        }
        if (filled($pin->emergency_coverage_text)) {
            $aeoReadiness += 25;
        }
        if (filled($service->ai_summary)) {
            $aeoReadiness += 15;
        }
        if (filled($pin->meta_description)) {
            $aeoReadiness += 10;
        }
        $aeoReadiness = min(100, $aeoReadiness);

        $localRelevance = 0;
        if (filled($pin->area_name)) {
            $localRelevance += 25;
        }
        if ($pin->nearbyAreas->isNotEmpty()) {
            $localRelevance += 25;
        }
        if ($pin->hospitals->isNotEmpty()) {
            $localRelevance += 25;
        }
        if (filled($pin->emergency_coverage_text)) {
            $localRelevance += 25;
        }
        $localRelevance = min(100, $localRelevance);

        $composite = (int) round(($contentUniqueness + $geoReadiness + $aeoReadiness + $localRelevance) / 4);

        $thresholds = config('services_master.quality_thresholds', []);
        $minComposite = (int) ($thresholds['composite_min'] ?? 40);
        $minContent = (int) ($thresholds['content_uniqueness_min'] ?? 35);
        $minGeo = (int) ($thresholds['geo_readiness_min'] ?? 35);

        $isIndexable = $composite >= $minComposite
            && $contentUniqueness >= $minContent
            && $geoReadiness >= $minGeo
            && $service->isListedPublicly();

        if ($contentUniqueness < $minContent) {
            $reasons[] = 'content_uniqueness_below_threshold';
        }
        if ($geoReadiness < $minGeo) {
            $reasons[] = 'geo_readiness_below_threshold';
        }
        if ($composite < $minComposite) {
            $reasons[] = 'composite_below_threshold';
        }

        return [
            'content_uniqueness' => $contentUniqueness,
            'geo_readiness' => $geoReadiness,
            'aeo_readiness' => $aeoReadiness,
            'local_relevance' => $localRelevance,
            'composite' => $composite,
            'is_indexable' => $isIndexable,
            'reasons' => $reasons,
        ];
    }

    public function persist(Service $service, PinCode $pin, ServiceLocationPage $mapping): array
    {
        $scores = $this->score($service, $pin, $mapping);

        $mapping->forceFill([
            'quality_snapshot' => array_merge($scores, ['scored_at' => now()->toIso8601String()]),
            'is_indexable' => $scores['is_indexable'],
        ])->saveQuietly();

        if ($mapping->page !== null) {
            $page = $mapping->page;
            $page->forceFill([
                'robots_meta' => $scores['is_indexable'] ? 'index,follow' : 'noindex,follow',
                'is_active' => $service->is_active && $service->publish_status === \App\Enums\PublishStatus::Published,
            ])->saveQuietly();
        }

        return $scores;
    }
}
