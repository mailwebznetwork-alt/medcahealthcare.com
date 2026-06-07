<?php

namespace App\Services\Seo;

use App\Models\BusinessProfile;
use App\Models\GeoLocation;
use App\Models\PinCode;
use Illuminate\Support\Facades\Schema;

/**
 * Database-first locality resolution — no hardcoded city or area names.
 */
class LocalityContextResolver
{
    public function primaryCity(): ?string
    {
        $profile = $this->businessProfile();
        if ($profile !== null && filled($profile->city)) {
            return trim((string) $profile->city);
        }

        if (Schema::hasTable('pin_codes')) {
            $city = PinCode::query()
                ->where('is_active', true)
                ->whereNotNull('city')
                ->where('city', '!=', '')
                ->orderByDesc('priority')
                ->value('city');

            if (is_string($city) && $city !== '') {
                return trim($city);
            }
        }

        return null;
    }

    public function primaryAreaLabel(): ?string
    {
        if (Schema::hasTable('geo_locations')) {
            $label = GeoLocation::query()
                ->where('is_active', true)
                ->whereNotNull('label')
                ->where('label', '!=', '')
                ->orderByDesc('id')
                ->value('label');

            if (is_string($label) && $label !== '') {
                return trim($label);
            }
        }

        if (Schema::hasTable('pin_codes')) {
            $area = PinCode::query()
                ->where('is_active', true)
                ->whereNotNull('area_name')
                ->where('area_name', '!=', '')
                ->orderByDesc('priority')
                ->value('area_name');

            if (is_string($area) && $area !== '') {
                return trim($area);
            }
        }

        return $this->primaryCity();
    }

    public function coverageRadiusKm(): ?float
    {
        if (! Schema::hasTable('geo_locations')) {
            return null;
        }

        $radius = GeoLocation::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->value('radius_km');

        return $radius !== null ? (float) $radius : null;
    }

    public function coverageDisplayText(): ?string
    {
        $radius = $this->coverageRadiusKm();
        $area = $this->primaryAreaLabel();
        $city = $this->primaryCity();

        if ($radius !== null && $area !== null && $city !== null) {
            return __(':radius km service radius around :area, :city', [
                'radius' => (int) $radius,
                'area' => $area,
                'city' => $city,
            ]);
        }

        if ($area !== null && $city !== null) {
            return __('Service coverage across :area, :city', [
                'area' => $area,
                'city' => $city,
            ]);
        }

        return $city;
    }

    public function pincodeRejectionHint(): string
    {
        $city = $this->primaryCity();

        return $city !== null
            ? __('We do not service that pincode yet. Try another :city pincode.', ['city' => $city])
            : __('We do not service that pincode yet.');
    }

    public function aiMarketContext(): string
    {
        $city = $this->primaryCity();
        $area = $this->primaryAreaLabel();

        if ($city !== null && $area !== null && $city !== $area) {
            return trim($city.' / '.$area);
        }

        return $city ?? $area ?? '';
    }

    private function businessProfile(): ?BusinessProfile
    {
        if (! Schema::hasTable('business_profiles')) {
            return null;
        }

        return BusinessProfile::query()
            ->where('website', config('app.url'))
            ->first()
            ?? BusinessProfile::query()->latest('id')->first();
    }
}
