<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CatalogNavigationTreeBuilder
{
    public function __construct(
        private readonly NavigationCatalogStateRepository $catalogState,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function buildPublicNodes(string $zone = 'header'): array
    {
        if (! config('navigation.auto_sync_catalog_under_services', true)) {
            return [];
        }

        if (! Schema::hasTable('service_categories') || ! Schema::hasTable('services')) {
            return [];
        }

        $exclusions = $this->catalogState->exclusions($zone);
        $manualChildren = $this->catalogState->manualChildren($zone);
        $siblingOrders = $this->catalogState->siblingOrders($zone);

        $categories = ServiceCategory::query()
            ->active()
            ->ordered()
            ->get();

        if ($categories->isEmpty()) {
            return [];
        }

        $categoriesByParent = $categories->groupBy(static fn (ServiceCategory $category): int => (int) ($category->parent_id ?? 0));

        $serviceIdsByCategory = $this->serviceIdsByCategoryId();
        $services = $this->publicServicesKeyed();
        $subServicesByService = $this->publicSubServicesGrouped();

        $roots = $categoriesByParent->get(0, collect());

        $nodes = $roots
            ->filter(fn (ServiceCategory $category): bool => $category->isListedPublicly())
            ->map(fn (ServiceCategory $category): array => $this->categoryNode(
                $category,
                $categoriesByParent,
                $serviceIdsByCategory,
                $services,
                $subServicesByService,
                $zone,
                $exclusions,
                $manualChildren,
                $siblingOrders,
            ))
            ->filter(fn (array $node): bool => $this->nodeHasContent($node))
            ->values()
            ->all();

        return $this->sortSiblingNodes($nodes, 'services', $siblingOrders);
    }

    /**
     * @param  Collection<int, Collection<int, ServiceCategory>>  $categoriesByParent
     * @param  Collection<int, list<int>>  $serviceIdsByCategory
     * @param  Collection<int, Service>  $services
     * @param  Collection<int, Collection<int, SubService>>  $subServicesByService
     * @param  list<string>  $exclusions
     * @param  array<string, list<array<string, mixed>>>  $manualChildren
     * @param  array<string, list<string>>  $siblingOrders
     * @return array<string, mixed>
     */
    private function categoryNode(
        ServiceCategory $category,
        Collection $categoriesByParent,
        Collection $serviceIdsByCategory,
        Collection $services,
        Collection $subServicesByService,
        string $zone,
        array $exclusions,
        array $manualChildren,
        array $siblingOrders,
    ): array {
        $catalogKey = 'category:'.$category->id;

        if (in_array($catalogKey, $exclusions, true)) {
            return ['children' => [], 'catalog_key' => $catalogKey];
        }

        $children = [];

        foreach ($categoriesByParent->get($category->id, collect()) as $childCategory) {
            if (! $childCategory->isListedPublicly()) {
                continue;
            }

            $childNode = $this->categoryNode(
                $childCategory,
                $categoriesByParent,
                $serviceIdsByCategory,
                $services,
                $subServicesByService,
                $zone,
                $exclusions,
                $manualChildren,
                $siblingOrders,
            );

            if ($this->nodeHasContent($childNode)) {
                $children[] = $childNode;
            }
        }

        foreach ($serviceIdsByCategory->get($category->id, []) as $serviceId) {
            $service = $services->get($serviceId);
            if ($service === null) {
                continue;
            }

            $children[] = $this->serviceNode($service, $subServicesByService, $zone, $exclusions, $manualChildren, $siblingOrders);
        }

        $children = array_merge(
            $children,
            $this->attachmentNodes($manualChildren[$catalogKey] ?? [], $catalogKey),
        );

        $children = $this->sortSiblingNodes($children, $catalogKey, $siblingOrders);

        return [
            'label' => $category->name,
            'href' => $category->publicUrl(),
            'children' => $children,
            'auto_synced' => true,
            'catalog_key' => $catalogKey,
            'item_type' => 'category',
            'service_category_id' => $category->id,
        ];
    }

    /**
     * @param  Collection<int, Collection<int, SubService>>  $subServicesByService
     * @param  list<string>  $exclusions
     * @param  array<string, list<array<string, mixed>>>  $manualChildren
     * @param  array<string, list<string>>  $siblingOrders
     * @return array<string, mixed>
     */
    private function serviceNode(
        Service $service,
        Collection $subServicesByService,
        string $zone,
        array $exclusions,
        array $manualChildren,
        array $siblingOrders,
    ): array {
        $catalogKey = 'service:'.$service->id;

        if (in_array($catalogKey, $exclusions, true)) {
            return ['children' => [], 'catalog_key' => $catalogKey];
        }

        $children = [];

        if (config('navigation.include_sub_services', true) && Schema::hasTable('sub_services')) {
            foreach ($subServicesByService->get($service->id, collect()) as $subService) {
                $subKey = 'sub_service:'.$subService->id;
                if (in_array($subKey, $exclusions, true)) {
                    continue;
                }

                $children[] = [
                    'label' => $subService->title,
                    'href' => $subService->publicUrl(),
                    'children' => [],
                    'auto_synced' => true,
                    'catalog_key' => $subKey,
                    'item_type' => 'sub_service',
                    'sub_service_id' => $subService->id,
                    'service_id' => $service->id,
                ];
            }
        }

        $children = array_merge(
            $children,
            $this->attachmentNodes($manualChildren[$catalogKey] ?? [], $catalogKey),
        );

        $children = $this->sortSiblingNodes($children, $catalogKey, $siblingOrders);

        return [
            'label' => $service->title,
            'href' => $service->publicUrl(),
            'children' => $children,
            'auto_synced' => true,
            'catalog_key' => $catalogKey,
            'item_type' => 'service',
            'service_id' => $service->id,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $attachments
     * @return list<array<string, mixed>>
     */
    private function attachmentNodes(array $attachments, string $parentCatalogKey): array
    {
        return array_values(array_map(function (array $node) use ($parentCatalogKey): array {
            $node['parent_catalog_key'] = $parentCatalogKey;
            $node['auto_synced'] = false;
            $node['catalog_attachment'] = true;
            $node['label'] = $node['title'] ?? $node['label'] ?? __('Menu item');

            return $node;
        }, $attachments));
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @param  array<string, list<string>>  $siblingOrders
     * @return list<array<string, mixed>>
     */
    private function sortSiblingNodes(array $nodes, string $contextKey, array $siblingOrders): array
    {
        $order = $siblingOrders[$contextKey] ?? null;
        if (! is_array($order) || $order === []) {
            return array_values($nodes);
        }

        $map = [];
        foreach ($nodes as $node) {
            $map[$this->nodeSortKey($node)] = $node;
        }

        $sorted = [];
        foreach ($order as $key) {
            if (isset($map[$key])) {
                $sorted[] = $map[$key];
                unset($map[$key]);
            }
        }

        foreach ($map as $node) {
            $sorted[] = $node;
        }

        return array_values($sorted);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function nodeSortKey(array $node): string
    {
        if (($node['auto_synced'] ?? false) && filled($node['catalog_key'] ?? null)) {
            return 'auto:'.(string) $node['catalog_key'];
        }

        if (filled($node['_attachment_id'] ?? null)) {
            return 'manual:'.(string) $node['_attachment_id'];
        }

        if (filled($node['id'] ?? null)) {
            return 'manual:'.(string) $node['id'];
        }

        return 'manual:'.md5(json_encode($node));
    }

    /**
     * @param  array{children?: list<array<string, mixed>>, href?: string|null}  $node
     */
    private function nodeHasContent(array $node): bool
    {
        $children = is_array($node['children'] ?? null) ? $node['children'] : [];

        return $children !== [] || filled($node['href'] ?? null);
    }

    /**
     * @return Collection<int, list<int>>
     */
    private function serviceIdsByCategoryId(): Collection
    {
        if (! Schema::hasTable('service_category_map')) {
            return collect();
        }

        return \Illuminate\Support\Facades\DB::table('service_category_map')
            ->orderBy('service_category_id')
            ->get(['service_category_id', 'service_id'])
            ->groupBy('service_category_id')
            ->map(static fn (Collection $rows): array => $rows->pluck('service_id')->map(static fn ($id): int => (int) $id)->unique()->values()->all());
    }

    /**
     * @return Collection<int, Service>
     */
    private function publicServicesKeyed(): Collection
    {
        return Service::query()
            ->publicListing()
            ->get(['id', 'title', 'service_code', 'sort_order'])
            ->keyBy('id');
    }

    /**
     * @return Collection<int, Collection<int, SubService>>
     */
    private function publicSubServicesGrouped(): Collection
    {
        if (! config('navigation.include_sub_services', true) || ! Schema::hasTable('sub_services')) {
            return collect();
        }

        return SubService::query()
            ->publicListing()
            ->get(['id', 'service_id', 'sub_service_code', 'title', 'sort_order'])
            ->groupBy('service_id');
    }
}
