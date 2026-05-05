<?php

namespace App\Services\Growth;

use App\Models\BusinessProfile;
use App\Models\GeoLocation;
use App\Models\Pincode;
use Illuminate\Support\Facades\Schema;

class GeoService
{
    protected function resolveBusinessProfileId(): int
    {
        return BusinessProfile::query()->firstOrCreate(
            ['website' => config('app.url')],
            [
                'name' => config('app.name'),
                'email' => config('mail.from.address'),
            ]
        )->id;
    }

    public function saveLocation(array $data): GeoLocation
    {
        return GeoLocation::query()->updateOrCreate(
            ['business_profile_id' => $this->resolveBusinessProfileId()],
            [
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'radius_km' => $data['radius_km'],
            ]
        );
    }

    public function addPincode(array $data): Pincode
    {
        return Pincode::query()->create([
            'business_profile_id' => $this->resolveBusinessProfileId(),
            'geo_location_id' => $data['geo_location_id'] ?? null,
            'pincode' => $data['pincode'],
            'serviceable' => (bool) ($data['serviceable'] ?? true),
            'landing_page' => $data['landing_page'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
        ]);
    }

    public function updatePincode(int $id, array $data): ?Pincode
    {
        $pincode = Pincode::query()->find($id);
        if (! $pincode instanceof Pincode) {
            return null;
        }

        $pincode->fill([
            'geo_location_id' => $data['geo_location_id'] ?? $pincode->geo_location_id,
            'pincode' => $data['pincode'] ?? $pincode->pincode,
            'serviceable' => array_key_exists('serviceable', $data) ? (bool) $data['serviceable'] : $pincode->serviceable,
            'landing_page' => $data['landing_page'] ?? $pincode->landing_page,
            'priority' => $data['priority'] ?? $pincode->priority,
        ])->save();

        return $pincode;
    }

    public function getCoverageStats(): array
    {
        if (! Schema::hasTable('geo_locations') || ! Schema::hasTable('pincodes')) {
            return [
                'total_locations' => 0,
                'total_pincodes' => 0,
                'serviceable_pincodes' => 0,
                'high_priority_pincodes' => 0,
            ];
        }

        return [
            'total_locations' => GeoLocation::query()->count(),
            'total_pincodes' => Pincode::query()->count(),
            'serviceable_pincodes' => Pincode::query()->where('serviceable', true)->count(),
            'high_priority_pincodes' => Pincode::query()->where('priority', 'high')->count(),
        ];
    }
}
