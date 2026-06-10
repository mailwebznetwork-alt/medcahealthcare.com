<?php

namespace App\Services\Bulk;

use App\Models\Block;
use App\Models\Blog;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\SectionLibraryItem;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\ActivityLogService;
use App\Services\Blocks\BlockTemplateSyncService;
use App\Services\Governance\AdminAuthorityGuard;
use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Operations\PinCodeDeletionService;
use App\Services\Operations\ServiceCategoryService;
use App\Services\Operations\ServiceLifecycle;
use App\Services\Operations\SubServiceDeletionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkActionService
{
    public function __construct(
        private readonly BulkGovernancePreview $preview,
        private readonly ActivityLogService $activityLog,
        private readonly AdminAuthorityGuard $authorityGuard,
        private readonly DownstreamArtifactPurger $purger,
        private readonly BulkDuplicateService $duplicateService,
        private readonly ServiceLifecycle $serviceLifecycle,
        private readonly ServiceCategoryService $categoryService,
        private readonly PinCodeDeletionService $pinCodeDeletionService,
        private readonly SubServiceDeletionService $subServiceDeletionService,
    ) {}

    /**
     * @param  list<int>  $ids
     * @return array<string, mixed>
     */
    public function governancePreview(string $resourceKey, array $ids, string $action): array
    {
        return match ($resourceKey) {
            'site_architect.pages' => $this->preview->forPages($ids, $action),
            'site_architect.blocks' => $this->preview->forBlocks($ids, $action),
            default => [
                'selected_count' => count($ids),
                'action' => $action,
                'affected_pages' => [],
                'affected_registry_rows' => [],
                'affected_mappings' => 0,
                'affected_urls' => [],
                'affected_location_pages' => [],
                'affected_service_pages' => [],
                'cascading_deletions' => [__('Bulk action will apply to :count selected row(s).', ['count' => count($ids)])],
                'requires_delete_confirmation' => in_array($action, ['delete'], true),
            ],
        };
    }

    /**
     * @param  list<int>  $ids
     * @return array{processed: int, skipped: int, message: string}
     */
    public function execute(string $resourceKey, array $ids, string $action, User $user): array
    {
        $config = config('bulk_actions.resources')[$resourceKey] ?? null;
        if (! is_array($config)) {
            return ['processed' => 0, 'skipped' => count($ids), 'message' => __('Unknown bulk resource.')];
        }

        $allowed = $config['actions'] ?? [];
        if (! in_array($action, $allowed, true)) {
            return ['processed' => 0, 'skipped' => count($ids), 'message' => __('Action not allowed for this resource.')];
        }

        if ($resourceKey === 'operations.pin_codes' && $action === 'delete') {
            return $this->executePinCodeBulkDelete($ids, $user, $config);
        }

        if ($resourceKey === 'operations.services' && $action === 'delete') {
            return $this->executeServiceBulkDelete($ids, $user, $config);
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $config['model'];
        $processed = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $model = $modelClass::query()->find($id);
            if ($model === null) {
                $skipped++;

                continue;
            }

            $ok = match ($resourceKey) {
                'site_architect.pages' => $this->executePageAction($model, $action, $user, $resourceKey),
                'site_architect.blogs' => $this->executeBlogAction($model, $action, $user, $resourceKey),
                'site_architect.blocks' => $this->executeBlockAction($model, $action, $user, $resourceKey),
                'site_architect.sections' => $this->executeSectionAction($model, $action, $user),
                'operations.pin_codes' => $this->executePinCodeAction($model, $action, $user),
                'operations.services' => $this->executeServiceAction($model, $action, $user, $resourceKey),
                'operations.service_categories' => $this->executeServiceCategoryAction($model, $action, $user, $resourceKey),
                'operations.sub_services' => $this->executeSubServiceAction($model, $action, $user),
                'operations.vacancies' => $this->executeVacancyAction($model, $action, $user, $resourceKey),
                default => false,
            };

            if ($ok) {
                $processed++;
            } else {
                $skipped++;
            }
        }

        $module = is_string($config['module'] ?? null) ? $config['module'] : 'site_architect';

        $this->activityLog->log(
            'bulk_'.$action,
            $module,
            strtoupper($resourceKey).' bulk '.$action.': processed='.$processed.', skipped='.$skipped.' by user '.$user->id,
        );

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'message' => __('Bulk :action complete. :processed processed, :skipped skipped.', [
                'action' => $action,
                'processed' => $processed,
                'skipped' => $skipped,
            ]),
        ];
    }

    /**
     * @param  list<int>  $ids
     */
    public function export(string $resourceKey, array $ids, string $format = 'json'): StreamedResponse
    {
        $config = config('bulk_actions.resources')[$resourceKey] ?? null;
        /** @var class-string<Model> $modelClass */
        $modelClass = is_array($config) ? $config['model'] : Model::class;
        $rows = $modelClass::query()->whereIn('id', $ids)->get();

        if ($format === 'csv') {
            return Response::streamDownload(function () use ($rows): void {
                $out = fopen('php://output', 'w');
                if ($out === false) {
                    return;
                }
                fputcsv($out, ['id', 'payload']);
                foreach ($rows as $row) {
                    fputcsv($out, [$row->getKey(), json_encode($row->toArray())]);
                }
                fclose($out);
            }, 'bulk-export-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
        }

        return Response::streamDownload(
            fn () => print ($rows->toJson(JSON_PRETTY_PRINT)),
            'bulk-export-'.now()->format('Y-m-d-His').'.json',
            ['Content-Type' => 'application/json'],
        );
    }

    private function executePageAction(Page $page, string $action, User $user, string $resourceKey): bool
    {
        if (! $user->can('update', $page) && ! in_array($action, ['delete', 'duplicate'], true)) {
            return false;
        }

        return match ($action) {
            'delete' => $this->deletePage($page, $user),
            'duplicate' => $this->duplicateService->duplicate($resourceKey, $page, $user),
            'publish' => (bool) $page->forceFill(['is_active' => true])->save(),
            'unpublish' => (bool) $page->forceFill(['is_active' => false])->save(),
            'export' => true,
            default => false,
        };
    }

    private function deletePage(Page $page, User $user): bool
    {
        if (! $user->can('delete', $page)) {
            return false;
        }

        $this->authorityGuard->markDeletedByAdmin($page);
        $this->purger->purgeForDeletedPage($page);
        $page->delete();
        $this->activityLog->log('page_delete', 'site_architect', 'Bulk delete page ID '.$page->id);

        return true;
    }

    private function executeBlogAction(Blog $blog, string $action, User $user, string $resourceKey): bool
    {
        return match ($action) {
            'delete' => (bool) tap($blog, fn (Blog $b) => $b->delete()),
            'duplicate' => $this->duplicateService->duplicate($resourceKey, $blog, $user),
            'publish' => (bool) $blog->forceFill(['is_published' => true, 'published_at' => $blog->published_at ?? now()])->save(),
            'unpublish' => (bool) $blog->forceFill(['is_published' => false])->save(),
            'export' => true,
            default => false,
        };
    }

    private function executeBlockAction(Block $block, string $action, User $user, string $resourceKey): bool
    {
        return match ($action) {
            'delete' => $this->deleteBlock($block),
            'duplicate' => $this->duplicateService->duplicate($resourceKey, $block, $user),
            'publish' => (bool) $block->forceFill(['is_active' => true])->save(),
            'unpublish' => (bool) $block->forceFill(['is_active' => false])->save(),
            'sync' => $this->syncBlock($block),
            'export' => true,
            default => false,
        };
    }

    private function deleteBlock(Block $block): bool
    {
        if ($block->is_managed) {
            return false;
        }

        $this->authorityGuard->markDeletedByAdmin($block);
        $block->delete();

        return true;
    }

    private function syncBlock(Block $block): bool
    {
        $result = app(BlockTemplateSyncService::class)->sync(slugs: [(string) $block->block_slug], backup: true);

        return in_array($block->block_slug, $result['synced'] ?? [], true);
    }

    private function executeSectionAction(SectionLibraryItem $section, string $action, User $user): bool
    {
        return match ($action) {
            'delete' => (bool) tap($section, fn (SectionLibraryItem $s) => $s->delete()),
            'export' => true,
            default => false,
        };
    }

    private function executePinCodeAction(PinCode $pinCode, string $action, User $user): bool
    {
        return match ($action) {
            'delete' => $this->deletePinCode($pinCode, $user),
            'publish' => $user->can('changeActiveState', $pinCode) && (bool) $pinCode->forceFill(['is_active' => true])->save(),
            'unpublish' => $user->can('changeActiveState', $pinCode) && (bool) $pinCode->forceFill(['is_active' => false])->save(),
            default => false,
        };
    }

    private function deletePinCode(PinCode $pinCode, User $user): bool
    {
        if (! $user->can('delete', $pinCode)) {
            return false;
        }

        $this->pinCodeDeletionService->delete($pinCode, 'bulk');

        return true;
    }

    /**
     * @param  list<int>  $ids
     * @param  array<string, mixed>  $config
     * @return array{processed: int, skipped: int, message: string}
     */
    /**
     * @param  list<int>  $ids
     * @param  array<string, mixed>  $config
     * @return array{processed: int, skipped: int, message: string}
     */
    private function executeServiceBulkDelete(array $ids, User $user, array $config): array
    {
        @ini_set('memory_limit', '256M');
        set_time_limit(300);

        $eligible = Service::query()
            ->whereIn('id', $ids)
            ->get()
            ->filter(fn (Service $service): bool => $user->can('delete', $service));

        $skipped = count($ids) - $eligible->count();
        $processed = $this->serviceLifecycle->deleteMany($eligible, 'bulk');

        $module = is_string($config['module'] ?? null) ? $config['module'] : 'operations';

        $this->activityLog->log(
            'bulk_delete',
            $module,
            'OPERATIONS.SERVICES bulk delete: processed='.$processed.', skipped='.$skipped.' by user '.$user->id,
        );

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'message' => __('Bulk delete complete. :processed removed, :skipped skipped.', [
                'processed' => $processed,
                'skipped' => $skipped,
            ]),
        ];
    }

    private function executePinCodeBulkDelete(array $ids, User $user, array $config): array
    {
        set_time_limit(300);

        $eligible = PinCode::query()
            ->whereIn('id', $ids)
            ->get()
            ->filter(fn (PinCode $pinCode): bool => $user->can('delete', $pinCode));

        $skipped = count($ids) - $eligible->count();
        $processed = $this->pinCodeDeletionService->deleteMany($eligible, 'bulk');

        $module = is_string($config['module'] ?? null) ? $config['module'] : 'operations';

        $this->activityLog->log(
            'bulk_delete',
            $module,
            'OPERATIONS.PIN_CODES bulk delete: processed='.$processed.', skipped='.$skipped.' by user '.$user->id,
        );

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'message' => __('Bulk delete complete. :processed removed, :skipped skipped.', [
                'processed' => $processed,
                'skipped' => $skipped,
            ]),
        ];
    }

    private function executeServiceAction(Service $service, string $action, User $user, string $resourceKey): bool
    {
        return match ($action) {
            'delete' => $this->deleteService($service, $user),
            'duplicate' => $this->duplicateService->duplicate($resourceKey, $service, $user),
            default => false,
        };
    }

    private function executeServiceCategoryAction(ServiceCategory $category, string $action, User $user, string $resourceKey): bool
    {
        return match ($action) {
            'delete' => $this->deleteServiceCategory($category, $user),
            'duplicate' => $this->duplicateService->duplicate($resourceKey, $category, $user),
            default => false,
        };
    }

    private function executeVacancyAction(Vacancy $vacancy, string $action, User $user, string $resourceKey): bool
    {
        return match ($action) {
            'delete' => $this->deleteVacancy($vacancy, $user),
            'duplicate' => $this->duplicateService->duplicate($resourceKey, $vacancy, $user),
            default => false,
        };
    }

    private function deleteService(Service $service, User $user): bool
    {
        if (! $user->can('delete', $service)) {
            return false;
        }

        $this->serviceLifecycle->delete($service);

        return true;
    }

    private function deleteServiceCategory(ServiceCategory $category, User $user): bool
    {
        if (! $user->can('delete', $category)) {
            return false;
        }

        $this->categoryService->delete($category);

        return true;
    }

    private function executeSubServiceAction(SubService $subService, string $action, User $user): bool
    {
        return match ($action) {
            'delete' => $this->deleteSubService($subService, $user),
            default => false,
        };
    }

    private function deleteSubService(SubService $subService, User $user): bool
    {
        if (! $user->can('delete', $subService)) {
            return false;
        }

        $this->subServiceDeletionService->delete($subService, 'bulk');

        return true;
    }

    private function deleteVacancy(Vacancy $vacancy, User $user): bool
    {
        if (! $user->can('delete', $vacancy)) {
            return false;
        }

        $vacancy->delete();

        return true;
    }
}
