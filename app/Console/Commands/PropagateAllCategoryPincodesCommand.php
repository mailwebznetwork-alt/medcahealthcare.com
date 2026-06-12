<?php

namespace App\Console\Commands;

use App\Models\ServiceCategory;
use App\Services\Operations\ServicePincodeCoverageService;
use Illuminate\Console\Command;

class PropagateAllCategoryPincodesCommand extends Command
{
    protected $signature = 'medca:propagate-all-category-pincodes';

    protected $description = 'Propagate every category GEO master to its primary-category services';

    public function handle(ServicePincodeCoverageService $coverage): int
    {
        $total = 0;

        ServiceCategory::query()->orderBy('id')->each(function (ServiceCategory $category) use ($coverage, &$total): void {
            $serviceIds = $coverage->propagateCategoryToServices($category);
            $count = count($serviceIds);
            $total += $count;
            if ($count > 0) {
                $this->line(sprintf('%s → %d service(s)', $category->code, $count));
            }
        });

        $this->info("Propagated category GEO to {$total} service assignment(s).");

        return self::SUCCESS;
    }
}
