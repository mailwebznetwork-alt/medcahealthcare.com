<?php

namespace App\Services\Launch;

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Import\ImportPipeline;
use App\Services\Operations\CategoryMasterOrchestrator;
use App\Services\Operations\ServiceMasterOrchestrator;
use App\Services\Operations\SubServiceMasterOrchestrator;
use App\Services\Seo\LocalityContextResolver;
use Database\Seeders\MedcaLaunchPagesSeeder;
use Database\Seeders\MedcaLaunchServicesSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ProductionPopulationService
{
    public function __construct(
        private readonly ImportPipeline $pipeline,
        private readonly ProductionTrackingConfigurator $tracking,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function populate(bool $enrichMedia = true): array
    {
        $path = (string) config('medca_launch.imports_path');
        $log = ['steps' => [], 'imports' => []];

        foreach (config('medca_launch.import_order', []) as $entity) {
            $file = $this->resolveImportFile($path, $entity);
            if ($file === null) {
                continue;
            }

            $result = $this->pipeline->commit($entity, $file, null, basename($file), false);
            $log['imports'][$entity] = $result;
            $log['steps'][] = "import:{$entity}";
        }

        if ($enrichMedia) {
            (new MedcaLaunchServicesSeeder)->run();
            $log['steps'][] = 'seed:MedcaLaunchServicesSeeder';
        }

        (new MedcaLaunchPagesSeeder)->run();
        $log['steps'][] = 'seed:MedcaLaunchPagesSeeder';

        $this->backfillPageCategories();
        $log['steps'][] = 'sync:page-categories';

        $enriched = $this->enrichRemainingPincodes();
        $log['steps'][] = "geo:enriched-remaining:{$enriched}";

        $this->syncServiceLocationMatrix();
        $log['steps'][] = 'sync:service-location-matrix';

        ServiceCategory::query()->active()->each(function (ServiceCategory $category): void {
            app(CategoryMasterOrchestrator::class)->sync($category);
        });
        $log['steps'][] = 'sync:category-pages';

        Service::query()->where('is_active', true)->each(function (Service $service): void {
            app(ServiceMasterOrchestrator::class)->sync($service);
        });
        $log['steps'][] = 'sync:service-orchestrator';

        \App\Models\SubService::query()->where('is_active', true)->each(function ($sub): void {
            app(SubServiceMasterOrchestrator::class)->sync($sub);
        });
        $log['steps'][] = 'sync:sub-service-pages';

        foreach (['medca:reconcile-service-location-matrix', 'medca:sync-page-registry'] as $command) {
            Artisan::call($command);
            $log['steps'][] = $command;
        }

        $this->syncInternalLinkSnapshots();
        $log['steps'][] = 'sync:internal-link-snapshots';

        $log['tracking'] = $this->tracking->configure();

        return $log;
    }

    private function backfillPageCategories(): void
    {
        $resolver = app(\App\Services\Operations\PageCategoryResolver::class);
        \App\Models\Page::query()->orderBy('id')->each(
            static fn (\App\Models\Page $page) => $resolver->applyToPage($page)
        );
    }

    private function syncInternalLinkSnapshots(): void
    {
        $linking = app(\App\Services\Operations\ServiceInternalLinkingEngine::class);
        $related = app(\App\Services\Discovery\RelatedContentEngine::class);

        ServiceCategory::query()->active()->each(fn (ServiceCategory $c) => $related->persistCategory($c, '560076'));
        Service::query()->where('is_active', true)->each(fn (Service $s) => $linking->persist($s->fresh(['pincodes', 'locationPages.page', 'categories'])));
        \App\Models\SubService::query()->where('is_active', true)->each(fn ($sub) => $related->persistSubService($sub));
    }

    private function syncServiceLocationMatrix(): void
    {
        $pinIds = PinCode::query()
            ->where('is_active', true)
            ->where('is_serviceable', true)
            ->pluck('id')
            ->all();

        if ($pinIds === []) {
            return;
        }

        Service::query()
            ->where('is_active', true)
            ->each(function (Service $service) use ($pinIds): void {
                $sync = [];
                foreach ($pinIds as $pinId) {
                    $sync[$pinId] = [
                        'priority' => 10,
                        'is_visible' => true,
                        'is_featured' => (bool) $service->is_featured,
                        'coverage_notes' => 'Medca home healthcare coverage',
                    ];
                }
                $service->pincodes()->sync($sync);
            });
    }

    private function enrichRemainingPincodes(): int
    {
        $count = 0;

        $city = app(LocalityContextResolver::class)->primaryCity() ?? 'service area';

        PinCode::query()
            ->where('is_active', true)
            ->where('is_serviceable', true)
            ->whereDoesntHave('landmarks')
            ->each(function (PinCode $pin) use (&$count, $city): void {
                $area = $pin->area_name ?: $city;
                $pin->update([
                    'coverage_text' => $pin->coverage_text ?: "Medca provides home nursing, elder care, physiotherapy, doctor visits, and lab collection across {$area} ({$pin->pincode}) within our {$city} service belt.",
                    'emergency_coverage_text' => $pin->emergency_coverage_text ?: '24×7 on-call physician escalation for active Medca care plans.',
                    'geo_page_ready' => true,
                ]);

                $pin->landmarks()->firstOrCreate(
                    ['name' => "{$area} Main Road"],
                    ['sort_order' => 0]
                );
                $pin->hospitals()->firstOrCreate(
                    ['name' => 'Partner Hospital Network'],
                    ['sort_order' => 0]
                );
                $pin->nearbyAreas()->firstOrCreate(
                    ['area_name' => "Adjacent {$city} neighbourhoods"],
                    ['sort_order' => 0]
                );
                $pin->locationFaqs()->firstOrCreate(
                    ['question' => "Do you cover {$area} ({$pin->pincode})?"],
                    ['answer' => 'Yes — Medca serves this pincode with home healthcare services after availability confirmation.', 'sort_order' => 0]
                );

                $count++;
            });

        return $count;
    }

    private function resolveImportFile(string $dir, string $entity): ?string
    {
        foreach (['csv', 'xlsx', 'xls'] as $ext) {
            $candidate = "{$dir}/{$entity}.{$ext}";
            if (File::isReadable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
