<?php

namespace App\Services\Growth;

use App\Models\GeoLocation;
use App\Models\Pincode;
use Illuminate\Support\Facades\Schema;

class GeoService
{
    public function saveLocation(array $data): GeoLocation
    {
        $location = GeoLocation::query()->latest('id')->first();

        if (! $location instanceof GeoLocation) {
            return GeoLocation::query()->create($data);
        }

        $location->fill($data)->save();

        return $location;
    }

    public function addPincode(array $data): Pincode
    {
        return Pincode::query()->create($data);
    }

    public function updatePincode(int $id, array $data): ?Pincode
    {
        $pincode = Pincode::query()->find($id);
        if (! $pincode instanceof Pincode) {
            return null;
        }

        $pincode->fill($data)->save();

        return $pincode;
    }

    public function getStats(): array
    {
        if (! Schema::hasTable('geo_locations') || ! Schema::hasTable('pincodes')) {
            return [
                'total_locations' => 0,
                'active_locations' => 0,
                'total_pincodes' => 0,
                'active_pincodes' => 0,
            ];
        }

        return [
            'total_locations' => GeoLocation::query()->count(),
            'active_locations' => GeoLocation::query()->where('is_active', true)->count(),
            'total_pincodes' => Pincode::query()->count(),
            'active_pincodes' => Pincode::query()->where('is_active', true)->count(),
        ];
    }
}
