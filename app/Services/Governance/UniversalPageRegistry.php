<?php

namespace App\Services\Governance;

use App\Enums\PageCategory;
use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use Illuminate\Support\Facades\Cache;

/**
 * Single registry for manual, generated, and planned pages.
 */
class UniversalPageRegistry
{
    public function __construct(
        private readonly PageOwnershipResolver $ownership,
        private readonly VisibilityGovernanceService $visibility,
    ) {}

    /**
     * @return array{synced: int, manual: int, generated: int, planned: int}
     */
    public function syncAll(): array
    {
        $counts = ['synced' => 0, 'manual' => 0, 'generated' => 0, 'planned' => 0];

        Page::query()->orderBy('id')->each(function (Page $page) use (&$counts): void {
            $this->upsertPageEntry($page);
            $counts['synced']++;
            $counts[$page->page_source === 'generated' ? 'generated' : 'manual']++;
        });

        ServiceCategory::query()->active()->ordered()->each(function (ServiceCategory $category) use (&$counts): void {
            $this->upsertCategoryEntry($category);
            $counts['synced']++;
            if ($category->page_id === null) {
                $counts['planned']++;
            }
        });

        Service::query()->whereNotNull('detail_page_id')->each(function (Service $service) use (&$counts): void {
            if ($service->detailPage !== null) {
                $this->upsertServiceEntry($service);
                $counts['synced']++;
            }
        });

        SubService::query()->each(function (SubService $sub) use (&$counts): void {
            $this->upsertSubServiceEntry($sub);
            $counts['synced']++;
            $counts['planned']++;
        });

        ServiceLocationPage::query()->with(['page', 'service', 'pincode'])->each(function (ServiceLocationPage $mapping) use (&$counts): void {
            if ($mapping->page !== null) {
                $this->upsertLocationEntry($mapping);
                $counts['synced']++;
            }
        });

        Cache::put('governance.registry.last_sync_at', now()->toIso8601String(), now()->addDays(90));
        Cache::put('governance.registry.last_sync_counts', $counts, now()->addDays(90));
        Cache::put('governance.registry.last_sync_status', 'ok', now()->addDays(90));

        return $counts;
    }

    public function upsertPageEntry(Page $page): PageRegistry
    {
        $ownership = $this->ownership->resolveForPage($page);
        $key = 'page:'.$page->slug;

        return PageRegistry::query()->updateOrCreate(
            ['registry_key' => $key],
            [
                'page_id' => $page->id,
                'entity_type' => 'page',
                'entity_id' => $page->id,
                'page_category' => $page->page_category?->value ?? PageCategory::Other->value,
                'owner' => $page->registry_owner ?: $ownership['owner'],
                'source' => $page->page_source ?: 'manual',
                'public_path' => $page->publicPath(),
                'is_listed' => (bool) $page->is_active,
                'visibility_snapshot' => $this->visibility->snapshotForPage($page),
                'ownership_snapshot' => $ownership,
            ]
        );
    }

    public function upsertCategoryEntry(ServiceCategory $category): PageRegistry
    {
        $ownership = $this->ownership->categoryOwnership();
        $key = 'category:'.$category->publicSlug();

        return PageRegistry::query()->updateOrCreate(
            ['registry_key' => $key],
            [
                'page_id' => $category->page_id,
                'entity_type' => 'category',
                'entity_id' => $category->id,
                'page_category' => PageCategory::Category->value,
                'owner' => 'operations_category',
                'source' => ($category->page_id || $category->linkedPage) ? 'generated' : 'planned',
                'public_path' => '/service-categories/'.$category->publicSlug(),
                'is_listed' => $category->isListedPublicly(),
                'visibility_snapshot' => $this->visibility->snapshotForCategory($category),
                'ownership_snapshot' => $ownership,
            ]
        );
    }

    public function upsertServiceEntry(Service $service): PageRegistry
    {
        $page = $service->detailPage;
        $ownership = $this->ownership->serviceOwnership();
        $key = 'service:'.$service->service_code;

        return PageRegistry::query()->updateOrCreate(
            ['registry_key' => $key],
            [
                'page_id' => $page?->id,
                'entity_type' => 'service',
                'entity_id' => $service->id,
                'page_category' => PageCategory::Service->value,
                'owner' => 'operations_service',
                'source' => $page?->page_source ?? 'generated',
                'public_path' => '/services/'.$service->service_code,
                'is_listed' => $service->isListedPublicly(),
                'visibility_snapshot' => $this->visibility->snapshotForService($service),
                'ownership_snapshot' => $ownership,
            ]
        );
    }

    public function upsertSubServiceEntry(SubService $sub): PageRegistry
    {
        $sub->loadMissing(['service', 'linkedPage']);
        $ownership = $this->ownership->subServiceOwnership();
        $key = 'sub_service:'.$sub->service?->service_code.':'.$sub->sub_service_code;
        $publicPath = config('phase2_discovery.sub_service_public_path_pattern', '/services/{code}/sub/{sub}');
        $publicPath = str_replace(
            ['{code}', '{sub}'],
            [$sub->service?->service_code ?? '', $sub->sub_service_code],
            $publicPath
        );

        return PageRegistry::query()->updateOrCreate(
            ['registry_key' => $key],
            [
                'page_id' => $sub->page_id,
                'entity_type' => 'sub_service',
                'entity_id' => $sub->id,
                'page_category' => PageCategory::SubService->value,
                'owner' => 'operations_sub_service',
                'source' => $sub->page_id ? 'generated' : 'planned',
                'public_path' => $publicPath,
                'is_listed' => $sub->isListedPublicly(),
                'visibility_snapshot' => $this->visibility->snapshotForSubService($sub),
                'ownership_snapshot' => $ownership,
            ]
        );
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
    public function purgeOrphans(): array
    {
        return app(DownstreamArtifactPurger::class)->purgeAllCatalogOrphans();
    }

    public function upsertLocationEntry(ServiceLocationPage $mapping): PageRegistry
    {
        $mapping->loadMissing(['service', 'page', 'pincode']);
        $ownership = $this->ownership->locationOwnership();
        $code = $mapping->service?->service_code ?? 'unknown';
        $pin = $mapping->pincode?->pincode ?? 'unknown';
        $key = 'location:'.$code.':'.$pin;

        return PageRegistry::query()->updateOrCreate(
            ['registry_key' => $key],
            [
                'page_id' => $mapping->page_id,
                'entity_type' => 'location',
                'entity_id' => $mapping->id,
                'page_category' => PageCategory::Location->value,
                'owner' => 'operations_location_matrix',
                'source' => 'generated',
                'public_path' => $mapping->publicUrl(),
                'is_listed' => $mapping->isPubliclyIndexable(),
                'visibility_snapshot' => $this->visibility->snapshotForLocationPage($mapping),
                'ownership_snapshot' => $ownership,
            ]
        );
    }
}
