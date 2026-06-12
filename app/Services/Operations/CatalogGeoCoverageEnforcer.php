<?php

namespace App\Services\Operations;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Governance\UniversalPageRegistry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Re-syncs catalog pages after GEO coverage changes (pincode delete/detach).
 * Service/category location pages are torn down without GEO; sub-service pages are not GEO-gated.
 */
final class CatalogGeoCoverageEnforcer
{
    public function __construct(
        private readonly ServiceMasterOrchestrator $serviceOrchestrator,
        private readonly CategoryMasterOrchestrator $categoryOrchestrator,
        private readonly UniversalPageRegistry $pageRegistry,
        private readonly DownstreamArtifactPurger $purger,
        private readonly ServiceLocationMatrixReconciler $matrixReconciler,
    ) {}

    /**
     * @param  list<int>  $pinIds
     */
    public function detachPivotsForPinIds(array $pinIds): void
    {
        if ($pinIds === []) {
            return;
        }

        DB::table('service_pincodes')->whereIn('pincode_id', $pinIds)->delete();
        DB::table('category_pincodes')->whereIn('pincode_id', $pinIds)->delete();
    }

    public function enforceAfterGeoRemoval(): void
    {
        Service::query()->orderBy('id')->each(function (Service $service): void {
            $this->serviceOrchestrator->sync(
                $service->fresh(['pincodes', 'seo', 'faqs', 'schema', 'subServices'])
            );
        });

        ServiceCategory::query()->orderBy('id')->each(function (ServiceCategory $category): void {
            $this->categoryOrchestrator->sync(
                $category->fresh(['seo', 'faqs', 'schema', 'pincodes'])
            );
        });

        $this->purger->purgeAfterCatalogEntityChange();
        $this->pageRegistry->syncAll();
    }

    /**
     * Full catalog rebuild after GEO is restored (pin import, category GEO save, etc.).
     */
    public function enforceAfterGeoRestore(): void
    {
        @set_time_limit(0);

        if (class_exists(\App\Console\Commands\PropagateAllCategoryPincodesCommand::class)) {
            Artisan::call('medca:propagate-all-category-pincodes');
        }

        ServiceCategory::query()->orderBy('id')->each(function (ServiceCategory $category): void {
            $this->categoryOrchestrator->sync(
                $category->fresh(['seo', 'faqs', 'schema', 'pincodes'])
            );
        });

        Service::query()->orderBy('id')->each(function (Service $service): void {
            $this->serviceOrchestrator->sync(
                $service->fresh(['pincodes', 'seo', 'faqs', 'schema', 'subServices'])
            );
        });

        $this->matrixReconciler->reconcile(purgeCatalogOrphans: true, refreshExisting: false);
        $this->pageRegistry->syncAll();
    }
}
