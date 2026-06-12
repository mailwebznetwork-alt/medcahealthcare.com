<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Public\CatalogLineIconMapper;
use App\Support\KeyBenefitNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncCatalogLineIconsCommand extends Command
{
    protected $signature = 'medca:sync-catalog-line-icons {--dry-run : Preview changes without writing}';

    protected $description = 'Populate line_icon and structured key_benefits icons across the catalog';

    public function handle(CatalogLineIconMapper $mapper): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $stats = [
            'categories' => 0,
            'services' => 0,
            'sub_services' => 0,
            'key_benefits' => 0,
        ];

        DB::transaction(function () use ($mapper, $dryRun, &$stats): void {
            ServiceCategory::query()->each(function (ServiceCategory $category) use ($mapper, $dryRun, &$stats): void {
                $icon = $mapper->categoryIcon($category->code, $category->name);
                $benefits = $this->normalizeBenefits($category->key_benefits, $mapper);

                if (! $dryRun) {
                    $category->forceFill([
                        'line_icon' => $icon,
                        'key_benefits' => $benefits,
                    ])->saveQuietly();
                }

                $stats['categories']++;
                if ($benefits !== []) {
                    $stats['key_benefits']++;
                }
            });

            Service::query()->each(function (Service $service) use ($mapper, $dryRun, &$stats): void {
                $icon = $mapper->serviceIcon($service->service_code, $service->title);
                $benefits = $this->normalizeBenefits($service->key_benefits, $mapper);

                if (! $dryRun) {
                    $service->forceFill([
                        'line_icon' => $icon,
                        'key_benefits' => $benefits,
                    ])->saveQuietly();
                }

                $stats['services']++;
                if ($benefits !== []) {
                    $stats['key_benefits']++;
                }
            });

            SubService::query()->each(function (SubService $subService) use ($mapper, $dryRun, &$stats): void {
                $icon = $mapper->subServiceIcon($subService->sub_service_code, $subService->title);
                $benefits = $this->normalizeBenefits($subService->key_benefits, $mapper);

                if (! $dryRun) {
                    $subService->forceFill([
                        'line_icon' => $icon,
                        'key_benefits' => $benefits,
                    ])->saveQuietly();
                }

                $stats['sub_services']++;
                if ($benefits !== []) {
                    $stats['key_benefits']++;
                }
            });
        });

        $this->info(sprintf(
            '%s categories, %s services, %s sub-services (%s with key benefit icons)',
            $stats['categories'],
            $stats['services'],
            $stats['sub_services'],
            $stats['key_benefits']
        ));

        if ($dryRun) {
            $this->warn('Dry run — no database changes written.');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array{label: string, icon: string}>
     */
    private function normalizeBenefits(mixed $raw, CatalogLineIconMapper $mapper): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $expanded = KeyBenefitNormalizer::expand($raw);

        return KeyBenefitNormalizer::serialize(array_map(
            static fn (array $item): array => [
                'label' => $item['label'],
                'icon' => filled($item['icon'] ?? null) ? $item['icon'] : $mapper->benefitIcon($item['label']),
            ],
            $expanded
        ));
    }
}
