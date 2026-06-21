<?php

namespace Database\Seeders;

use App\Models\BangaloreZone;
use Illuminate\Database\Seeder;

class BangaloreZonesSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            ['code' => 'south', 'name' => 'South Bangalore', 'slug' => 'south-bangalore', 'sort_order' => 1],
            ['code' => 'north', 'name' => 'North Bangalore', 'slug' => 'north-bangalore', 'sort_order' => 2],
            ['code' => 'east', 'name' => 'East Bangalore', 'slug' => 'east-bangalore', 'sort_order' => 3],
            ['code' => 'west', 'name' => 'West Bangalore', 'slug' => 'west-bangalore', 'sort_order' => 4],
            ['code' => 'central', 'name' => 'Central Bangalore', 'slug' => 'central-bangalore', 'sort_order' => 5],
            ['code' => 'arekere-belt', 'name' => 'Karnataka Belt (25km radius)', 'slug' => 'arekere-belt', 'sort_order' => 6],
        ];

        foreach ($zones as $zone) {
            BangaloreZone::query()->updateOrCreate(
                ['code' => $zone['code']],
                array_merge($zone, ['is_active' => true])
            );
        }
    }
}
