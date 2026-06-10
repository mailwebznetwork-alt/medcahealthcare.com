<?php

namespace App\Services;

use App\Models\SiteNavigationItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SiteNavigationTreeService
{
    public function __construct(
        private readonly SiteNavigationLinkResolver $linkResolver,
        private readonly SiteNavigationCatalogMerger $catalogMerger,
    ) {}

    /**
     * @return Collection<int, SiteNavigationItem>
     */
    public function rootsForZone(string $zone): Collection
    {
        if (! Schema::hasTable('site_navigation_items')) {
            return collect();
        }

        return SiteNavigationItem::query()
            ->where('zone', $zone)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with($this->eagerRelations())
            ->get()
            ->each(fn (SiteNavigationItem $root) => $this->loadChildrenRecursive($root));
    }

    public function loadChildrenRecursive(SiteNavigationItem $item, int $depth = 0): void
    {
        if ($depth > 12) {
            return;
        }

        $children = SiteNavigationItem::query()
            ->where('parent_id', $item->id)
            ->orderBy('sort_order')
            ->with($this->eagerRelations())
            ->get();

        $item->setRelation('children', $children);

        foreach ($children as $child) {
            $this->loadChildrenRecursive($child, $depth + 1);
        }
    }

    /**
     * @return list<string>
     */
    public function eagerRelations(): array
    {
        return ['page:id,title,slug,is_active', 'serviceCategory:id,name,code,slug,is_active', 'service:id,title,service_code,is_active', 'subService:id,service_id,sub_service_code,title,is_active'];
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    public function syncZone(string $zone, array $nodes): void
    {
        if (! Schema::hasTable('site_navigation_items')) {
            return;
        }

        $nodes = $this->catalogMerger->stripCatalogChildrenForPersistence($nodes);

        DB::transaction(function () use ($zone, $nodes): void {
            SiteNavigationItem::query()->where('zone', $zone)->delete();
            $this->insertNodes($zone, $nodes, null);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    private function insertNodes(string $zone, array $nodes, ?int $parentId, int $depth = 0): void
    {
        if ($depth > 12) {
            return;
        }

        foreach (array_values($nodes) as $order => $node) {
            if (! is_array($node)) {
                continue;
            }

            $item = SiteNavigationItem::query()->create([
                'zone' => $zone,
                'parent_id' => $parentId,
                'sort_order' => $order,
                'item_type' => (string) ($node['item_type'] ?? SiteNavigationItem::TYPE_PAGE),
                'page_id' => filled($node['page_id'] ?? null) ? (int) $node['page_id'] : null,
                'service_category_id' => filled($node['service_category_id'] ?? null) ? (int) $node['service_category_id'] : null,
                'service_id' => filled($node['service_id'] ?? null) ? (int) $node['service_id'] : null,
                'sub_service_id' => filled($node['sub_service_id'] ?? null) ? (int) $node['sub_service_id'] : null,
                'custom_url' => filled($node['custom_url'] ?? null) ? (string) $node['custom_url'] : null,
                'title' => filled($node['title'] ?? null) ? (string) $node['title'] : null,
                'custom_label' => filled($node['custom_label'] ?? null) ? (string) $node['custom_label'] : null,
            ]);

            $children = $node['children'] ?? [];
            if (is_array($children) && $children !== []) {
                $this->insertNodes($zone, $children, $item->id, $depth + 1);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function serializeZone(string $zone): array
    {
        $tree = $this->rootsForZone($zone)
            ->map(fn (SiteNavigationItem $item): array => $this->serializeItem($item))
            ->values()
            ->all();

        return $this->catalogMerger->mergeCatalogUnderServices($tree, $zone);
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeItem(SiteNavigationItem $item): array
    {
        $children = SiteNavigationItem::query()
            ->where('parent_id', $item->id)
            ->orderBy('sort_order')
            ->with($this->eagerRelations())
            ->get();

        return [
            'id' => $item->id,
            'item_type' => $item->item_type,
            'page_id' => $item->page_id,
            'service_category_id' => $item->service_category_id,
            'service_id' => $item->service_id,
            'sub_service_id' => $item->sub_service_id,
            'custom_url' => $item->custom_url,
            'title' => $item->title,
            'custom_label' => $item->custom_label,
            'label' => $this->linkResolver->label($item),
            'children' => $children
                ->map(fn (SiteNavigationItem $child): array => $this->serializeItem($child))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<array{label: string, href: string|null, children: list<array<string, mixed>>}>
     */
    public function publicNavForZone(string $zone): array
    {
        $nodes = [];
        foreach ($this->rootsForZone($zone) as $root) {
            if (! $this->isItemActive($root)) {
                continue;
            }
            $nodes[] = $this->toPublicNode($root);
        }

        return array_values(array_filter($nodes));
    }

    /**
     * @return array{label: string, href: string|null, children: list<array<string, mixed>>}|null
     */
    private function toPublicNode(SiteNavigationItem $item): ?array
    {
        if (! $this->isItemActive($item)) {
            return null;
        }

        $childItems = SiteNavigationItem::query()
            ->where('parent_id', $item->id)
            ->orderBy('sort_order')
            ->with($this->eagerRelations())
            ->get();

        $children = [];
        foreach ($childItems as $child) {
            $node = $this->toPublicNode($child);
            if ($node !== null) {
                $children[] = $node;
            }
        }

        $href = $this->linkResolver->href($item);
        if ($href === null && $children === []) {
            if ($item->item_type === SiteNavigationItem::TYPE_GROUP && filled($item->title)) {
                return [
                    'label' => $this->linkResolver->label($item),
                    'href' => null,
                    'children' => [],
                ];
            }

            return null;
        }

        return [
            'label' => $this->linkResolver->label($item),
            'href' => $href,
            'children' => $children,
        ];
    }

    private function isItemActive(SiteNavigationItem $item): bool
    {
        return match ($item->item_type) {
            SiteNavigationItem::TYPE_PAGE => $item->page !== null && $item->page->is_active,
            SiteNavigationItem::TYPE_CATEGORY => $item->serviceCategory !== null && $item->serviceCategory->is_active,
            SiteNavigationItem::TYPE_SERVICE => $item->service !== null && $item->service->is_active,
            SiteNavigationItem::TYPE_SUB_SERVICE => $item->subService !== null && $item->subService->is_active,
            SiteNavigationItem::TYPE_URL => filled($item->custom_url),
            SiteNavigationItem::TYPE_GROUP => true,
            default => false,
        };
    }
}
