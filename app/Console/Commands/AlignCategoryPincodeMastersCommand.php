<?php

namespace App\Console\Commands;

use App\Models\PinCode;
use App\Models\ServiceCategory;
use App\Services\Operations\ServicePincodeCoverageService;
use App\Services\Operations\ServiceLocationMatrixReconciler;
use Illuminate\Console\Command;

class AlignCategoryPincodeMastersCommand extends Command
{
    protected $signature = 'medca:align-category-pincode-masters
                            {--category= : Limit to one category code}
                            {--reconcile : Run location-page matrix reconcile after propagation}';

    protected $description = 'Assign all active serviceable pincodes to category GEO masters and propagate to primary-category services';

    public function handle(
        ServicePincodeCoverageService $coverage,
        ServiceLocationMatrixReconciler $reconciler,
    ): int {
        $pinIds = PinCode::query()
            ->where('is_active', true)
            ->where('is_serviceable', true)
            ->orderBy('pincode')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($pinIds === []) {
            $this->error('No active serviceable pincodes found.');

            return self::FAILURE;
        }

        $query = ServiceCategory::query()->ordered();
        if ($code = $this->option('category')) {
            $query->where('code', ServiceCategory::normalizeCode($code));
        }

        $categories = $query->get();
        if ($categories->isEmpty()) {
            $this->error('No categories matched.');

            return self::FAILURE;
        }

        $allServiceIds = [];

        foreach ($categories as $category) {
            $serviceIds = $coverage->syncCategoryPincodes($category, $pinIds, 'ui', deferServicePropagation: false);
            $allServiceIds = array_merge($allServiceIds, $serviceIds);
            $this->info(sprintf(
                '%s: %d master pincodes → %d primary services',
                $category->code,
                $category->fresh()->pincodes()->count(),
                count($serviceIds),
            ));
        }

        if ($this->option('reconcile')) {
            $this->info('Reconciling location pages for '.count(array_unique($allServiceIds)).' services…');
            $report = $reconciler->reconcileMany(array_unique($allServiceIds), purgeCatalogOrphans: false);
            $this->table(
                ['Metric', 'Count'],
                collect($report)->except('issues')->map(fn ($v, $k) => [$k, $v])->values()->all()
            );
        }

        return self::SUCCESS;
    }
}
