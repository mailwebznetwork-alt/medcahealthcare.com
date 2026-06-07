<?php

namespace App\Services\Discovery\Expansion;

use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Seo\LocalityContextResolver;

class GeoExpansionEngine
{
    public function __construct(
        private readonly LocalityContextResolver $locality,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function signalsForCategory(ServiceCategory $category): array
    {
        $category->loadMissing('seo');

        return $category->seo?->geo_signals ?: array_filter([
            'primary_city' => $this->locality->primaryCity(),
            'primary_area' => $this->locality->primaryAreaLabel(),
            'coverage_text' => $this->locality->coverageDisplayText(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function signalsForLocation(PinCode $pin): array
    {
        $pin->loadMissing(['landmarks', 'hospitals', 'nearbyAreas']);

        return array_filter([
            'pincode' => $pin->pincode,
            'area_name' => $pin->area_name,
            'city' => $pin->city,
            'state' => $pin->state,
            'coverage_text' => $pin->coverage_text,
            'emergency_coverage_text' => $pin->emergency_coverage_text,
            'landmarks' => $pin->landmarks->pluck('name')->all(),
            'hospitals' => $pin->hospitals->pluck('name')->all(),
            'nearby_areas' => $pin->nearbyAreas->pluck('area_name')->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function entityTagsForService(Service $service, ?PinCode $pin = null): array
    {
        $tags = [];
        if ($pin !== null) {
            $tags[] = $pin->area_name ?: $pin->city;
            $tags[] = $pin->pincode;
        } else {
            $city = $this->locality->primaryCity();
            if ($city) {
                $tags[] = $city;
            }
        }

        return array_values(array_filter($tags));
    }
}
