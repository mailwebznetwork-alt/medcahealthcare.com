<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Operations\ServiceCategoryService;
use App\Services\Operations\ServiceLifecycle;
use App\Services\Operations\SubServiceDeletionService;
use App\Support\FakerContentGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeFakerDataCommand extends Command
{
    protected $signature = 'medca:purge-faker-data {--dry-run : List matches without deleting}';

    protected $description = 'Remove Faker / test placeholder catalog rows and orphan CMS pages from production';

    public function handle(
        FakerContentGuard $guard,
        ServiceCategoryService $categoryService,
        ServiceLifecycle $serviceLifecycle,
        SubServiceDeletionService $subServiceDeletion,
        DownstreamArtifactPurger $purger,
    ): int {
        if (! $guard->applies()) {
            $this->warn('This command is intended for production. Running anyway.');
        }

        $dryRun = (bool) $this->option('dry-run');
        $removed = [
            'categories' => 0,
            'services' => 0,
            'sub_services' => 0,
            'pages' => 0,
        ];

        ServiceCategory::query()->orderBy('id')->each(function (ServiceCategory $category) use ($guard, $categoryService, $dryRun, &$removed): void {
            if (! $guard->isCatalogFaker($category->name, $category->code, $category->description)) {
                return;
            }

            $this->line("category: {$category->code} ({$category->name})");

            if (! $dryRun) {
                $categoryService->delete($category);
            }

            $removed['categories']++;
        });

        Service::query()->orderBy('id')->each(function (Service $service) use ($guard, $serviceLifecycle, $dryRun, &$removed): void {
            if (! $guard->isCatalogFaker($service->title, $service->service_code, strip_tags((string) $service->description))) {
                return;
            }

            $this->line("service: {$service->service_code} ({$service->title})");

            if (! $dryRun) {
                $serviceLifecycle->delete($service);
            }

            $removed['services']++;
        });

        SubService::query()->orderBy('id')->each(function (SubService $subService) use ($guard, $subServiceDeletion, $dryRun, &$removed): void {
            if (! $guard->isCatalogFaker($subService->title, $subService->sub_service_code, strip_tags((string) $subService->description))) {
                return;
            }

            $this->line("sub_service: {$subService->sub_service_code} ({$subService->title})");

            if (! $dryRun) {
                $subServiceDeletion->delete($subService, 'system', 'Purge Faker placeholder data');
            }

            $removed['sub_services']++;
        });

        Page::query()
            ->orderBy('id')
            ->each(function (Page $page) use ($guard, $purger, $dryRun, &$removed): void {
                if (! $guard->isFakerLike($page->title) && ! $guard->isFakerLike($page->slug) && ! $guard->isFakerLike(strip_tags((string) $page->content))) {
                    return;
                }

                $this->line("page: {$page->slug} ({$page->title})");

                if ($dryRun) {
                    $removed['pages']++;

                    return;
                }

                DB::transaction(function () use ($page, $purger): void {
                    $purger->purgeForDeletedPage($page);
                    $page->delete();
                });

                $removed['pages']++;
            });

        if (! $dryRun && ($removed['categories'] + $removed['services'] + $removed['sub_services'] + $removed['pages']) > 0) {
            $purger->purgeAfterCatalogEntityChange();
        }

        $this->info(($dryRun ? 'Would remove: ' : 'Removed: ').json_encode($removed));

        return self::SUCCESS;
    }
}
