<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Medca build & launch — populates services, pages, pincodes, and global contact content.
 */
class MedcaLaunchSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GlobalContentVariableSeeder::class,
            MedcaBangalorePinCodesSeeder::class,
            MedcaLaunchGlobalContentSeeder::class,
            MedcaLaunchServicesSeeder::class,
            MedcaLaunchPagesSeeder::class,
        ]);
    }
}
