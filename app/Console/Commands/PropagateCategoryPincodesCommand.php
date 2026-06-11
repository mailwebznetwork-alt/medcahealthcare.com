<?php

namespace App\Console\Commands;

use App\Models\ServiceCategory;
use App\Services\Operations\ServicePincodeCoverageService;
use Illuminate\Console\Command;

class PropagateCategoryPincodesCommand extends Command
{
    protected $signature = 'medca:propagate-category-pincodes {category : Service category ID}';

    protected $description = 'Propagate category master pincodes to primary-category services (post HTTP save)';

    public function handle(ServicePincodeCoverageService $coverage): int
    {
        $category = ServiceCategory::query()->find((int) $this->argument('category'));
        if ($category === null) {
            $this->error('Category not found.');

            return self::FAILURE;
        }

        $serviceIds = $coverage->propagateCategoryToServices($category);
        $this->info('Propagated to '.count($serviceIds).' service(s) for '.$category->code);

        return self::SUCCESS;
    }
}
