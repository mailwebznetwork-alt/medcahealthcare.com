<?php

namespace App\Services\Governance;

use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use Illuminate\Support\Facades\Cache;

/**
 * Purges registry, cache, and downstream artifacts when database records no longer exist.
 * Never recreates missing database records.
 */
class DownstreamArtifactPurger
{
    public function __construct(
        private readonly AutomatedWriteAuditLogger $audit,
    ) {}

    /**
     * @return list<array{registry_key: string, entity_type: string, entity_id: int|null, page_id: int|null}>
     */
    public function previewRegistryOrphans(): array
    {
        $orphans = [];

        PageRegistry::query()->orderBy('id')->each(function (PageRegistry $entry) use (&$orphans): void {
            if ($this->registryEntryIsOrphan($entry)) {
                $orphans[] = [
                    'registry_key' => $entry->registry_key,
                    'entity_type' => $entry->entity_type,
                    'entity_id' => $entry->entity_id,
                    'page_id' => $entry->page_id,
                ];
            }
        });

        return $orphans;
    }

    public function countRegistryOrphans(): int
    {
        return count($this->previewRegistryOrphans());
    }

    /**
     * @return array{registry_removed: int, issues: list<string>}
     */
    public function purgeRegistryOrphans(): array
    {
        $removed = 0;
        $issues = [];

        PageRegistry::query()->orderBy('id')->each(function (PageRegistry $entry) use (&$removed, &$issues): void {
            if ($this->registryEntryIsOrphan($entry)) {
                $key = $entry->registry_key;
                $entry->delete();
                $removed++;

                $this->audit->log(
                    process: 'DownstreamArtifactPurger',
                    action: 'purge_registry_orphan',
                    table: 'page_registry',
                    recordId: null,
                    recordKey: $key,
                    outcome: 'applied',
                    reason: 'Database entity no longer exists; registry follows database.',
                );

                $issues[] = "Removed orphan registry key: {$key}";
            }
        });

        return ['registry_removed' => $removed, 'issues' => $issues];
    }

    /**
     * Generated location CMS pages with no service_location_pages mapping (e.g. after pincode delete bypass).
     *
     * @return list<array{id: int, slug: string}>
     */
    public function previewOrphanLocationPages(): array
    {
        $orphans = [];
        $mappedPageIds = $this->mappedLocationPageIds();

        $this->orphanLocationPageQuery($mappedPageIds)
            ->orderBy('id')
            ->each(function (Page $page) use (&$orphans): void {
                $orphans[] = [
                    'id' => $page->id,
                    'slug' => $page->slug,
                ];
            });

        return $orphans;
    }

    /**
     * @return array{pages_removed: int, issues: list<string>}
     */
    public function purgeOrphanLocationPages(): array
    {
        $removed = 0;
        $issues = [];
        $mappedPageIds = $this->mappedLocationPageIds();

        $this->orphanLocationPageQuery($mappedPageIds)
            ->orderBy('id')
            ->each(function (Page $page) use (&$removed, &$issues): void {
                $slug = $page->slug;
                $this->purgeForDeletedPage($page);
                $page->delete();
                $removed++;

                $this->audit->log(
                    process: 'DownstreamArtifactPurger',
                    action: 'purge_orphan_location_page',
                    table: 'pages',
                    recordId: null,
                    recordKey: $slug,
                    outcome: 'applied',
                    reason: 'Location CMS page has no service_location_pages mapping; database is authoritative.',
                );

                $issues[] = "Removed orphan location page: {$slug}";
            });

        if ($removed > 0) {
            $this->forgetPageCaches();
        }

        return ['pages_removed' => $removed, 'issues' => $issues];
    }

    /**
     * @return array{
     *     registry_removed: int,
     *     location_pages_removed: int,
     *     service_pages_removed: int,
     *     sub_service_pages_removed: int,
     *     category_pages_removed: int,
     *     issues: list<string>
     * }
     */
    public function purgeAllCatalogOrphans(): array
    {
        $registry = $this->purgeRegistryOrphans();
        $location = $this->purgeOrphanLocationPages();
        $service = $this->purgeOrphanServiceDetailPages();
        $subService = $this->purgeOrphanSubServicePages();
        $category = $this->purgeOrphanCategoryPages();

        return [
            'registry_removed' => $registry['registry_removed'],
            'location_pages_removed' => $location['pages_removed'],
            'service_pages_removed' => $service['pages_removed'],
            'sub_service_pages_removed' => $subService['pages_removed'],
            'category_pages_removed' => $category['pages_removed'],
            'issues' => array_merge(
                $registry['issues'],
                $location['issues'],
                $service['issues'],
                $subService['issues'],
                $category['issues'],
            ),
        ];
    }

    /**
     * Run after any catalog entity delete/detach so stray CMS pages cannot survive.
     *
     * @return array{
     *     registry_removed: int,
     *     location_pages_removed: int,
     *     service_pages_removed: int,
     *     sub_service_pages_removed: int,
     *     category_pages_removed: int,
     *     issues: list<string>
     * }
     */
    public function purgeAfterCatalogEntityChange(): array
    {
        return $this->purgeAllCatalogOrphans();
    }

    /**
     * After bulk catalog deletes, related pages and mappings are already removed in SQL batches.
     */
    public function purgeAfterBulkCatalogDeletion(): array
    {
        $registry = $this->purgeRegistryOrphans();

        $this->forgetPageCaches();

        return [
            'registry_removed' => $registry['registry_removed'],
            'location_pages_removed' => 0,
            'service_pages_removed' => 0,
            'sub_service_pages_removed' => 0,
            'category_pages_removed' => 0,
            'issues' => $registry['issues'],
        ];
    }

    /** @deprecated Use purgeAfterBulkCatalogDeletion() */
    public function purgeAfterBulkPinCodeDeletion(): array
    {
        return $this->purgeAfterBulkCatalogDeletion();
    }

    /**
     * @return array{pages_removed: int, issues: list<string>}
     */
    public function purgeOrphanServiceDetailPages(): array
    {
        return $this->purgeOrphanPagesOfType(
            query: $this->orphanServiceDetailPageQuery($this->mappedServiceDetailPageIds()),
            auditAction: 'purge_orphan_service_page',
            auditReason: 'Service detail CMS page is not linked to any service; database is authoritative.',
            issuePrefix: 'Removed orphan service page',
        );
    }

    /**
     * @return array{pages_removed: int, issues: list<string>}
     */
    public function purgeOrphanSubServicePages(): array
    {
        return $this->purgeOrphanPagesOfType(
            query: $this->orphanSubServicePageQuery($this->mappedSubServicePageIds()),
            auditAction: 'purge_orphan_sub_service_page',
            auditReason: 'Sub-service CMS page is not linked to any sub-service; database is authoritative.',
            issuePrefix: 'Removed orphan sub-service page',
        );
    }

    /**
     * @return array{pages_removed: int, issues: list<string>}
     */
    public function purgeOrphanCategoryPages(): array
    {
        return $this->purgeOrphanPagesOfType(
            query: $this->orphanCategoryPageQuery($this->mappedCategoryPageIds()),
            auditAction: 'purge_orphan_category_page',
            auditReason: 'Category CMS page is not linked to any category; database is authoritative.',
            issuePrefix: 'Removed orphan category page',
        );
    }

    public function purgeForDeletedPage(Page $page): void
    {
        PageRegistry::query()
            ->where(function ($query) use ($page): void {
                $query->where('page_id', $page->id)
                    ->orWhere(fn ($q) => $q->where('entity_type', 'page')->where('entity_id', $page->id));
            })
            ->delete();

        $this->forgetPageCaches();
    }

    public function purgeForDeletedService(Service $service): void
    {
        PageRegistry::query()
            ->where('entity_type', 'service')
            ->where('entity_id', $service->id)
            ->delete();

        PageRegistry::query()
            ->where('registry_key', 'service:'.$service->service_code)
            ->delete();

        $this->forgetPageCaches();
    }

    public function purgeForDeletedLocationMapping(ServiceLocationPage $mapping): void
    {
        $mapping->loadMissing(['service', 'pincode']);

        $code = $mapping->service?->service_code ?? 'unknown';
        $pin = $mapping->pincode?->pincode ?? 'unknown';

        PageRegistry::query()
            ->where('registry_key', 'location:'.$code.':'.$pin)
            ->delete();

        if ($mapping->page_id !== null) {
            PageRegistry::query()->where('page_id', $mapping->page_id)->delete();
        }
    }

    public function purgeForDeletedCategory(ServiceCategory $category): void
    {
        PageRegistry::query()
            ->where('entity_type', 'category')
            ->where('entity_id', $category->id)
            ->delete();

        PageRegistry::query()
            ->where('registry_key', 'category:'.$category->publicSlug())
            ->delete();

        $this->forgetPageCaches();
    }

    public function purgeForDeletedSubService(SubService $sub): void
    {
        $sub->loadMissing('service');

        PageRegistry::query()
            ->where('entity_type', 'sub_service')
            ->where('entity_id', $sub->id)
            ->delete();

        PageRegistry::query()
            ->where('registry_key', 'sub_service:'.$sub->service?->service_code.':'.$sub->sub_service_code)
            ->delete();

        $this->forgetPageCaches();
    }

    public function purgeForDeletedPinCode(PinCode $pinCode): void
    {
        ServiceLocationPage::query()
            ->where('pincode_id', $pinCode->id)
            ->with(['service', 'pincode'])
            ->each(function (ServiceLocationPage $mapping): void {
                $this->purgeForDeletedLocationMapping($mapping);
            });

        $this->forgetPageCaches();
    }

    /**
     * @param  list<int>  $mappedPageIds
     * @return \Illuminate\Database\Eloquent\Builder<Page>
     */
    private function orphanLocationPageQuery(array $mappedPageIds)
    {
        return Page::query()
            ->where(function ($query): void {
                $query->where('registry_owner', 'operations_location_matrix')
                    ->orWhere('slug', 'like', 'service-%-loc-%');
            })
            ->when(
                $mappedPageIds !== [],
                fn ($query) => $query->whereNotIn('id', $mappedPageIds),
                fn ($query) => $query,
            );
    }

    /**
     * @return list<int>
     */
    private function mappedLocationPageIds(): array
    {
        return ServiceLocationPage::query()
            ->whereNotNull('page_id')
            ->pluck('page_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function mappedCategoryPageIds(): array
    {
        return ServiceCategory::query()
            ->whereNotNull('page_id')
            ->pluck('page_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $mappedPageIds
     * @return \Illuminate\Database\Eloquent\Builder<Page>
     */
    private function orphanServiceDetailPageQuery(array $mappedPageIds)
    {
        return Page::query()
            ->where(function ($query): void {
                $query->where('registry_owner', 'operations_service')
                    ->orWhere(function ($inner): void {
                        $inner->where('slug', 'like', 'service-%')
                            ->where('slug', 'not like', '%-loc-%')
                            ->where('slug', 'not like', '%-sub-%');
                    });
            })
            ->when(
                $mappedPageIds !== [],
                fn ($query) => $query->whereNotIn('id', $mappedPageIds),
                fn ($query) => $query,
            );
    }

    /**
     * @param  list<int>  $mappedPageIds
     * @return \Illuminate\Database\Eloquent\Builder<Page>
     */
    private function orphanSubServicePageQuery(array $mappedPageIds)
    {
        return Page::query()
            ->where(function ($query): void {
                $query->where('registry_owner', 'operations_sub_service')
                    ->orWhere('slug', 'like', 'service-%-sub-%');
            })
            ->when(
                $mappedPageIds !== [],
                fn ($query) => $query->whereNotIn('id', $mappedPageIds),
                fn ($query) => $query,
            );
    }

    /**
     * @param  list<int>  $mappedPageIds
     * @return \Illuminate\Database\Eloquent\Builder<Page>
     */
    private function orphanCategoryPageQuery(array $mappedPageIds)
    {
        return Page::query()
            ->where(function ($query): void {
                $query->where('registry_owner', 'operations_category')
                    ->orWhere('slug', 'like', 'category-%');
            })
            ->when(
                $mappedPageIds !== [],
                fn ($query) => $query->whereNotIn('id', $mappedPageIds),
                fn ($query) => $query,
            );
    }

    /**
     * @return list<int>
     */
    private function mappedServiceDetailPageIds(): array
    {
        return Service::query()
            ->whereNotNull('detail_page_id')
            ->pluck('detail_page_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function mappedSubServicePageIds(): array
    {
        return SubService::query()
            ->whereNotNull('page_id')
            ->pluck('page_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Page>  $query
     * @return array{pages_removed: int, issues: list<string>}
     */
    private function purgeOrphanPagesOfType($query, string $auditAction, string $auditReason, string $issuePrefix): array
    {
        $removed = 0;
        $issues = [];

        $query->orderBy('id')->each(function (Page $page) use (&$removed, &$issues, $auditAction, $auditReason, $issuePrefix): void {
            $slug = $page->slug;
            $this->purgeForDeletedPage($page);
            $page->delete();
            $removed++;

            $this->audit->log(
                process: 'DownstreamArtifactPurger',
                action: $auditAction,
                table: 'pages',
                recordId: null,
                recordKey: $slug,
                outcome: 'applied',
                reason: $auditReason,
            );

            $issues[] = "{$issuePrefix}: {$slug}";
        });

        if ($removed > 0) {
            $this->forgetPageCaches();
        }

        return ['pages_removed' => $removed, 'issues' => $issues];
    }

    private function registryEntryIsOrphan(PageRegistry $entry): bool
    {
        return match ($entry->entity_type) {
            'page' => $entry->page_id === null || ! Page::query()->whereKey($entry->page_id)->exists(),
            'service' => $entry->entity_id === null || ! Service::query()->whereKey($entry->entity_id)->exists(),
            'category' => $entry->entity_id === null || ! ServiceCategory::query()->whereKey($entry->entity_id)->exists(),
            'sub_service' => $entry->entity_id === null || ! SubService::query()->whereKey($entry->entity_id)->exists(),
            'location' => $entry->entity_id === null || ! ServiceLocationPage::query()->whereKey($entry->entity_id)->exists(),
            default => $entry->page_id !== null && ! Page::query()->whereKey($entry->page_id)->exists(),
        };
    }

    private function forgetPageCaches(): void
    {
        try {
            Cache::tags(['pages', 'sitemap', 'registry'])->flush();
        } catch (\Throwable) {
            // Tag driver may be unavailable; best-effort only.
        }
    }
}
