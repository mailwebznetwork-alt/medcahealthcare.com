<?php

namespace App\Services;

use App\Models\Page;
use App\Models\SiteNavigationItem;
use Illuminate\Support\Facades\Schema;

class SiteNavigationCatalogMerger
{
    private ?int $servicesPageId = null;

    public function __construct(
        private readonly CatalogNavigationTreeBuilder $catalogTreeBuilder,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    public function mergeCatalogUnderServices(array $nodes, string $zone): array
    {
        if (! $this->shouldSyncZone($zone)) {
            return $nodes;
        }

        $catalogChildren = $this->catalogTreeBuilder->buildPublicNodes($zone);

        foreach ($nodes as $index => $node) {
            if (! $this->isServicesAnchor($node)) {
                continue;
            }

            $manualExtras = $this->manualChildrenExcludingCatalog($node);
            $nodes[$index]['children'] = array_values(array_merge($catalogChildren, $manualExtras));

            return $nodes;
        }

        return $nodes;
    }

    /**
     * Remove catalog-managed children under Services before persisting manual trees.
     *
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    public function stripCatalogChildrenForPersistence(array $nodes): array
    {
        foreach ($nodes as $index => $node) {
            if (! $this->isServicesAnchor($node)) {
                continue;
            }

            $nodes[$index]['children'] = $this->manualChildrenExcludingCatalog($node);

            return $nodes;
        }

        return $nodes;
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    public function pruneStoredCatalogChildrenUnderServices(string $zone): void
    {
        if (! Schema::hasTable('site_navigation_items') || ! $this->shouldSyncZone($zone)) {
            return;
        }

        $servicesAnchor = SiteNavigationItem::query()
            ->where('zone', $zone)
            ->whereNull('parent_id')
            ->where('item_type', SiteNavigationItem::TYPE_PAGE)
            ->where('page_id', $this->servicesPageId())
            ->first();

        if ($servicesAnchor === null) {
            return;
        }

        $this->deleteCatalogDescendants((int) $servicesAnchor->id);
    }

    private function deleteCatalogDescendants(int $parentId): void
    {
        $children = SiteNavigationItem::query()
            ->where('parent_id', $parentId)
            ->get();

        foreach ($children as $child) {
            if (in_array($child->item_type, [
                SiteNavigationItem::TYPE_CATEGORY,
                SiteNavigationItem::TYPE_SERVICE,
                SiteNavigationItem::TYPE_SUB_SERVICE,
            ], true)) {
                $this->deleteCatalogDescendants((int) $child->id);
                $child->delete();
            }
        }
    }

    /**
     * @param  array<string, mixed>  $node
     * @return list<array<string, mixed>>
     */
    private function manualChildrenExcludingCatalog(array $node): array
    {
        $children = is_array($node['children'] ?? null) ? $node['children'] : [];

        return array_values(array_filter(
            $children,
            static fn (array $child): bool => ! in_array((string) ($child['item_type'] ?? ''), [
                SiteNavigationItem::TYPE_CATEGORY,
                SiteNavigationItem::TYPE_SERVICE,
                SiteNavigationItem::TYPE_SUB_SERVICE,
            ], true) && ! ($child['auto_synced'] ?? false),
        ));
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function isServicesAnchor(array $node): bool
    {
        if (($node['item_type'] ?? null) === SiteNavigationItem::TYPE_PAGE) {
            $pageId = $this->servicesPageId();

            return $pageId !== null && (int) ($node['page_id'] ?? 0) === $pageId;
        }

        $href = $node['href'] ?? null;
        if (! is_string($href) || $href === '') {
            return false;
        }

        $servicesUrl = rtrim(url('/services'), '/');

        return rtrim($href, '/') === $servicesUrl;
    }

    private function servicesPageId(): ?int
    {
        if ($this->servicesPageId !== null) {
            return $this->servicesPageId;
        }

        if (! Schema::hasTable('pages')) {
            return null;
        }

        /** @var list<string> $slugs */
        $slugs = config('navigation.services_page_slugs', ['services']);

        $this->servicesPageId = Page::query()
            ->whereIn('slug', $slugs)
            ->where('is_active', true)
            ->value('id');

        return $this->servicesPageId !== null ? (int) $this->servicesPageId : null;
    }

    private function shouldSyncZone(string $zone): bool
    {
        if (! config('navigation.auto_sync_catalog_under_services', true)) {
            return false;
        }

        /** @var list<string> $zones */
        $zones = config('navigation.catalog_sync_zones', ['header']);

        return in_array($zone, $zones, true);
    }
}
