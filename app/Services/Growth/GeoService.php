<?php

namespace App\Services\Growth;

use App\Models\BusinessProfile;
use App\Models\GeoLocation;
use App\Models\PinCode;
use App\Services\Governance\PinCodeCreationGuard;
use App\Services\Governance\PinCodeMasterDataAudit;
use App\Services\Seo\LocalityContextResolver;
use Illuminate\Support\Facades\Schema;

class GeoService
{
    protected function resolveBusinessProfileId(): int
    {
        return BusinessProfile::query()->firstOrCreate(
            ['website' => config('app.url')],
            [
                'name' => config('medca.brand_name', 'MarkOnMinds'),
                'email' => config('mail.from.address'),
            ]
        )->id;
    }

    public function saveLocation(array $data): GeoLocation
    {
        $resolver = app(LocalityContextResolver::class);
        $defaultLabel = $resolver->primaryAreaLabel() ?? $resolver->primaryCity() ?? __('Service area');
        $label = trim((string) ($data['label'] ?? $defaultLabel));

        return GeoLocation::query()->updateOrCreate(
            ['business_profile_id' => $this->resolveBusinessProfileId()],
            [
                'label' => $label !== '' ? $label : $defaultLabel,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'radius_km' => $data['radius_km'],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]
        );
    }

    public function addPincode(array $data): ?PinCode
    {
        $pincode = trim((string) ($data['pincode'] ?? ''));
        $guard = app(PinCodeCreationGuard::class);
        $normalized = $guard->normalizePincode($pincode);

        if ($normalized === null) {
            return null;
        }

        $existing = PinCode::query()->where('pincode', $normalized)->first();
        if ($existing === null && ! $guard->canCreatePincode($normalized, 'growth')) {
            return null;
        }

        $pinCode = PinCode::query()->updateOrCreate(
            ['pincode' => $pincode],
            [
                'business_profile_id' => $this->resolveBusinessProfileId(),
                'geo_location_id' => $data['geo_location_id'] ?? null,
                'area_name' => trim((string) ($data['area_name'] ?? 'Area '.$pincode)),
                'city' => trim((string) ($data['city'] ?? app(LocalityContextResolver::class)->primaryCity() ?? '')),
                'is_serviceable' => (bool) ($data['serviceable'] ?? true),
                'landing_page' => $data['landing_page'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'is_active' => true,
            ]
        );

        if ($existing === null) {
            app(PinCodeMasterDataAudit::class)->created($pinCode, 'growth');
        } else {
            app(PinCodeMasterDataAudit::class)->updated($pinCode, 'growth');
        }

        return $pinCode;
    }

    public function updatePincode(int $id, array $data): ?PinCode
    {
        $pincode = PinCode::query()->find($id);
        if (! $pincode instanceof PinCode) {
            return null;
        }

        $pincode->fill([
            'geo_location_id' => $data['geo_location_id'] ?? $pincode->geo_location_id,
            'pincode' => $data['pincode'] ?? $pincode->pincode,
            'is_serviceable' => array_key_exists('serviceable', $data) ? (bool) $data['serviceable'] : $pincode->is_serviceable,
            'landing_page' => $data['landing_page'] ?? $pincode->landing_page,
            'priority' => $data['priority'] ?? $pincode->priority,
        ])->save();

        return $pincode;
    }

    public function getCoverageStats(): array
    {
        if (! Schema::hasTable('geo_locations') || ! Schema::hasTable('pin_codes')) {
            return [
                'total_locations' => 0,
                'total_pincodes' => 0,
                'serviceable_pincodes' => 0,
                'high_priority_pincodes' => 0,
            ];
        }

        return [
            'total_locations' => GeoLocation::query()->count(),
            'total_pincodes' => PinCode::query()->count(),
            'serviceable_pincodes' => PinCode::query()->where('is_serviceable', true)->count(),
            'high_priority_pincodes' => PinCode::query()->where('priority', 'high')->count(),
        ];
    }
}
