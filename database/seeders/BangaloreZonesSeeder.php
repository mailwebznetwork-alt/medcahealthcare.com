<?php

namespace Database\Seeders;

use App\Models\IndiaZone;
use Illuminate\Database\Seeder;

class IndiaZonesSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            ['code' => 'south', 'name' => 'South India', 'slug' => 'south-bangalore', 'sort_order' => 1],
            ['code' => 'north', 'name' => 'North India', 'slug' => 'north-bangalore', 'sort_order' => 2],
            ['code' => 'east', 'name' => 'East India', 'slug' => 'east-bangalore', 'sort_order' => 3],
            ['code' => 'west', 'name' => 'West India', 'slug' => 'west-bangalore', 'sort_order' => 4],
            ['code' => 'central', 'name' => 'Central India', 'slug' => 'central-bangalore', 'sort_order' => 5],
            ['code' => 'arekere-belt', 'name' => 'India Belt (25km radius)', 'slug' => 'arekere-belt', 'sort_order' => 6],
        ];

        foreach ($zones as $zone) {
            IndiaZone::query()->updateOrCreate(
                ['code' => $zone['code']],
                array_merge($zone, ['is_active' => true])
            );
        }
    }
}
