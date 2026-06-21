<?php

namespace Database\Seeders;

use App\Models\PinCode;
use App\Services\Governance\MasterDataProtection;
use App\Services\Governance\PinCodeCreationGuard;
use Illuminate\Database\Seeder;

/**
 * Optional bootstrap only — adds missing belt pincodes via updateOrCreate.
 * Never deletes existing Operations pin_codes rows. For production recovery use
 * storage/app/backups/database-*.sqlite or restore-points instead.
 */
class MedcaBangalorePinCodesSeeder extends Seeder
{
    public function run(): void
    {
        if (! app(MasterDataProtection::class)->allowsWrite('seeder')) {
            return;
        }

        $guard = app(PinCodeCreationGuard::class);

        foreach ($this->definitions() as $row) {
            $exists = PinCode::query()->where('pincode', $row['pincode'])->exists();
            if (! $exists && ! $guard->canCreatePincode($row['pincode'], 'seeder')) {
                continue;
            }

            PinCode::query()->updateOrCreate(
                ['pincode' => $row['pincode']],
                [
                    'area_name' => $row['area_name'],
                    'city' => 'Bangalore',
                    'locality' => $row['locality'] ?? null,
                    'is_serviceable' => true,
                    'is_active' => true,
                    'priority' => $row['priority'] ?? 'medium',
                    'geo_page_ready' => false,
                ]
            );
        }
    }

    /**
     * @return list<array{pincode: string, area_name: string, locality?: string, priority?: string}>
     */
    private function definitions(): array
    {
        return [
            ['pincode' => '560076', 'area_name' => 'Karnataka', 'priority' => 'high'],
            ['pincode' => '560083', 'area_name' => 'Bannerghatta Road', 'priority' => 'high'],
            ['pincode' => '560029', 'area_name' => 'BTM Layout', 'priority' => 'high'],
            ['pincode' => '560030', 'area_name' => 'BTM 2nd Stage', 'priority' => 'medium'],
            ['pincode' => '560011', 'area_name' => 'Jayanagar', 'priority' => 'medium'],
            ['pincode' => '560041', 'area_name' => 'Jayanagar South', 'priority' => 'medium'],
            ['pincode' => '560078', 'area_name' => 'JP Nagar', 'priority' => 'high'],
            ['pincode' => '560034', 'area_name' => 'Koramangala', 'priority' => 'high'],
            ['pincode' => '560095', 'area_name' => 'Koramangala 5th Block', 'priority' => 'medium'],
            ['pincode' => '560102', 'area_name' => 'HSR Layout', 'priority' => 'high'],
            ['pincode' => '560100', 'area_name' => 'Electronic City', 'priority' => 'medium'],
            ['pincode' => '560068', 'area_name' => 'Bommanahalli / Begur / Kudlu', 'priority' => 'medium'],
            ['pincode' => '560114', 'area_name' => 'Singasandra', 'priority' => 'medium'],
            ['pincode' => '560070', 'area_name' => 'Bannerghatta', 'priority' => 'medium'],
            ['pincode' => '560099', 'area_name' => 'Hulimavu', 'priority' => 'medium'],
        ];
    }
}
