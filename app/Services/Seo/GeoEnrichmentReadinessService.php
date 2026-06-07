<?php

namespace App\Services\Seo;

use App\Models\PinCode;
use App\Models\ServiceLocationPage;

/**
 * Audits whether pincode/location architecture supports GEO enrichment.
 */
class GeoEnrichmentReadinessService
{
    /**
     * @return array<string, mixed>
     */
    public function audit(): array
    {
        $totalPincodes = PinCode::count();
        $withLandmarks = PinCode::query()->whereHas('landmarks')->count();
        $withHospitals = PinCode::query()->whereHas('hospitals')->count();
        $withNearby = PinCode::query()->whereHas('nearbyAreas')->count();
        $withFaqs = PinCode::query()->whereHas('locationFaqs')->count();
        $withCoverage = PinCode::query()->whereNotNull('coverage_text')->where('coverage_text', '!=', '')->count();
        $withGeoFk = PinCode::query()->whereNotNull('geo_location_id')->count();
        $locationPages = ServiceLocationPage::count();
        $indexable = ServiceLocationPage::query()->where('is_indexable', true)->count();

        return [
            'pin_codes_total' => $totalPincodes,
            'with_landmarks' => $withLandmarks,
            'with_hospitals' => $withHospitals,
            'with_nearby_areas' => $withNearby,
            'with_location_faqs' => $withFaqs,
            'with_coverage_text' => $withCoverage,
            'with_geo_location_fk' => $withGeoFk,
            'location_pages' => $locationPages,
            'indexable_location_pages' => $indexable,
            'enrichment_tables' => [
                'pin_code_landmarks' => true,
                'pin_code_hospitals' => true,
                'pin_code_nearby_areas' => true,
                'pin_code_location_faqs' => true,
            ],
            'geo_entity_builder' => UnifiedJsonLdGraphBuilder::class,
            'readiness_score' => $this->readinessScore($totalPincodes, $withLandmarks, $withHospitals, $withCoverage),
        ];
    }

    private function readinessScore(int $total, int $landmarks, int $hospitals, int $coverage): int
    {
        if ($total === 0) {
            return 0;
        }

        $landmarkPct = ($landmarks / $total) * 100;
        $hospitalPct = ($hospitals / $total) * 100;
        $coveragePct = ($coverage / $total) * 100;

        return (int) round(($landmarkPct + $hospitalPct + $coveragePct) / 3);
    }
}
