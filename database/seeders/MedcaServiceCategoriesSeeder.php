<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Governance\CategoryCreationGuard;
use App\Services\Governance\MasterDataProtection;
use Illuminate\Database\Seeder;

class MedcaServiceCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        if (! app(MasterDataProtection::class)->allowsWrite('seeder')) {
            return;
        }

        $guard = app(CategoryCreationGuard::class);

        $definitions = [
            ['name' => 'Home Care', 'code' => 'home-care', 'sort_order' => 10],
            ['name' => 'Consulting Services', 'code' => 'consulting-services', 'sort_order' => 20],
            ['name' => 'Advisory', 'code' => 'elder-care', 'sort_order' => 30],
            ['name' => 'Post Hospital Care', 'code' => 'post-hospital-care', 'sort_order' => 40],
            ['name' => 'Consulting', 'code' => 'consulting', 'sort_order' => 50],
            ['name' => 'Consultations', 'code' => 'doctor-visits', 'sort_order' => 60],
        ];

        $ids = [];
        foreach ($definitions as $row) {
            $exists = ServiceCategory::query()->where('code', $row['code'])->exists();
            if (! $exists && ! $guard->canCreateCategory($row['code'], 'seeder')) {
                continue;
            }

            $category = ServiceCategory::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'description' => null,
                    'parent_id' => null,
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ]
            );
            $ids[] = $category->id;
        }

        if ($ids === []) {
            return;
        }

        Service::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(6)
            ->get()
            ->each(function (Service $service, int $index) use ($ids): void {
                $categoryId = $ids[$index % count($ids)] ?? null;
                if ($categoryId === null) {
                    return;
                }
                $service->categories()->syncWithoutDetaching([$categoryId]);
            });
    }
}
