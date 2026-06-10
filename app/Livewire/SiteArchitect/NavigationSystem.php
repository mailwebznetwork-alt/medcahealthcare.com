<?php

namespace App\Livewire\SiteArchitect;

use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SiteNavigationItem;
use App\Models\SubService;
use App\Services\ActivityLogService;
use App\Services\Growth\AiPulseService;
use App\Services\Integrations\OutboundWebhookDispatcher;
use App\Services\NavigationCatalogStateRepository;
use App\Services\SiteNavigationCatalogMerger;
use App\Services\SiteNavigationTreeService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class NavigationSystem extends Component
{
    use AuthorizesRequests;

    /** @var list<array<string, mixed>> */
    public array $headerTree = [];

    /** @var list<array<string, mixed>> */
    public array $footerTree = [];

    public string $poolSearch = '';

    public ?string $lastSavedAt = null;

    public string $addZone = 'header';

    public string $addType = SiteNavigationItem::TYPE_PAGE;

    public string $addTitle = '';

    public string $addCustomUrl = '';

    public ?int $addPageId = null;

    public ?int $addCategoryId = null;

    public ?int $addServiceId = null;

    public ?int $addSubServiceId = null;

    /** @var list<int> */
    public array $addParentPath = [];

    public ?string $addParentCatalogKey = null;

    /** @var list<int>|null */
    public ?array $editPath = null;

    public string $editZone = 'header';

    public function mount(SiteNavigationTreeService $treeService, SiteNavigationCatalogMerger $catalogMerger): void
    {
        $this->authorize('viewAny', Page::class);
        $catalogMerger->pruneStoredCatalogChildrenUnderServices(SiteNavigationItem::ZONE_HEADER);
        $this->reloadTrees($treeService);
    }

    public function render(): View
    {
        return view('livewire.site-architect.navigation-system', [
            'poolPages' => $this->poolPages(),
            'livePageCount' => Page::query()->where('is_active', true)->count(),
            'categoryOptions' => $this->categoryOptions(),
            'serviceOptions' => $this->serviceOptions(),
            'subServiceOptions' => $this->subServiceOptions(),
        ]);
    }

    public function saveTrees(SiteNavigationTreeService $treeService): void
    {
        $this->authorize('viewAny', Page::class);

        $treeService->syncZone(SiteNavigationItem::ZONE_HEADER, $this->headerTree);
        $treeService->syncZone(SiteNavigationItem::ZONE_FOOTER, $this->footerTree);

        $this->lastSavedAt = Carbon::now()->timezone(config('app.timezone'))->format('M j, Y g:i A');

        app(ActivityLogService::class)->log('navigation_menu_update', 'site_architect', 'Nested navigation saved');

        app(AiPulseService::class)->triggerAuditAfterPublish();
        app(OutboundWebhookDispatcher::class)->dispatch('navigation.updated', [
            'header_tree' => $this->headerTree,
            'footer_tree' => $this->footerTree,
        ]);

        $this->reloadTrees($treeService);
    }

    public function queueAddChild(array $parentPath, string $zone, ?string $parentCatalogKey = null): void
    {
        $this->addParentPath = array_values(array_map(static fn ($v) => (int) $v, $parentPath));
        $this->addParentCatalogKey = $parentCatalogKey;
        $this->addZone = $zone;
        $this->editPath = null;
    }

    public function clearAddTarget(): void
    {
        $this->addParentPath = [];
        $this->addParentCatalogKey = null;
    }

    public function queueEditItem(string $zone, array $path): void
    {
        $this->authorize('viewAny', Page::class);

        $tree = $zone === SiteNavigationItem::ZONE_FOOTER ? $this->footerTree : $this->headerTree;
        $node = $this->nodeAtPath($tree, $path);

        if ($node === null || ($node['auto_synced'] ?? false)) {
            return;
        }

        $this->editPath = array_values(array_map(static fn ($v) => (int) $v, $path));
        $this->editZone = $zone;
        $this->addZone = $zone;
        $this->addParentPath = [];
        $this->addParentCatalogKey = null;
        $this->addType = (string) ($node['item_type'] ?? SiteNavigationItem::TYPE_PAGE);
        $this->addTitle = (string) ($node['title'] ?? $node['custom_label'] ?? $node['label'] ?? '');
        $this->addCustomUrl = (string) ($node['custom_url'] ?? '');
        $this->addPageId = filled($node['page_id'] ?? null) ? (int) $node['page_id'] : null;
        $this->addCategoryId = filled($node['service_category_id'] ?? null) ? (int) $node['service_category_id'] : null;
        $this->addServiceId = filled($node['service_id'] ?? null) ? (int) $node['service_id'] : null;
        $this->addSubServiceId = filled($node['sub_service_id'] ?? null) ? (int) $node['sub_service_id'] : null;
    }

    public function cancelEdit(): void
    {
        $this->editPath = null;
        $this->resetAddForm();
    }

    public function addMenuItem(
        SiteNavigationTreeService $treeService,
        NavigationCatalogStateRepository $catalogState,
    ): void {
        $this->authorize('viewAny', Page::class);

        $node = $this->buildNodeFromForm();
        if ($node === null) {
            return;
        }

        if ($this->editPath !== null) {
            $this->saveEditedMenuItem($treeService, $catalogState, $node);

            return;
        }

        if ($this->addParentCatalogKey !== null && $this->addParentCatalogKey !== '') {
            $catalogState->addManualChild($this->addZone, $this->addParentCatalogKey, $node);
            $this->resetAddForm();
            $this->reloadTrees($treeService);

            return;
        }

        $tree = $this->addZone === SiteNavigationItem::ZONE_FOOTER ? 'footerTree' : 'headerTree';

        if ($this->addParentPath === []) {
            $this->{$tree}[] = $node;
        } else {
            $this->{$tree} = $this->insertChildAtPath($this->{$tree}, $this->addParentPath, $node);
        }

        $this->resetAddForm();
        $treeService->syncZone(
            $this->addZone,
            $this->addZone === SiteNavigationItem::ZONE_FOOTER ? $this->footerTree : $this->headerTree
        );
        $this->reloadTrees($treeService);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected function saveEditedMenuItem(
        SiteNavigationTreeService $treeService,
        NavigationCatalogStateRepository $catalogState,
        array $node,
    ): void {
        $treeProp = $this->editZone === SiteNavigationItem::ZONE_FOOTER ? 'footerTree' : 'headerTree';
        $tree = $this->{$treeProp};
        $existing = $this->nodeAtPath($tree, $this->editPath ?? []);

        if ($existing === null) {
            return;
        }

        if (($existing['catalog_attachment'] ?? false) && filled($existing['parent_catalog_key'] ?? null) && filled($existing['_attachment_id'] ?? null)) {
            $catalogState->removeManualChild(
                $this->editZone,
                (string) $existing['parent_catalog_key'],
                (string) $existing['_attachment_id'],
            );
            $node['_attachment_id'] = (string) $existing['_attachment_id'];
            $catalogState->addManualChild($this->editZone, (string) $existing['parent_catalog_key'], $node);
        } else {
            $tree = $this->replaceAtPath($tree, $this->editPath ?? [], $node);
            $this->{$treeProp} = $tree;
            $treeService->syncZone($this->editZone, $tree);
        }

        $this->editPath = null;
        $this->resetAddForm();
        $this->reloadTrees($treeService);
    }

    /**
     * @param  list<int>  $path
     */
    public function removeMenuItem(
        string $zone,
        array $path,
        SiteNavigationTreeService $treeService,
        NavigationCatalogStateRepository $catalogState,
    ): void {
        $this->authorize('viewAny', Page::class);

        $treeProp = $zone === SiteNavigationItem::ZONE_FOOTER ? 'footerTree' : 'headerTree';
        $tree = $this->{$treeProp};
        $node = $this->nodeAtPath($tree, $path);

        if ($node === null) {
            return;
        }

        if (($node['auto_synced'] ?? false) && filled($node['catalog_key'] ?? null)) {
            $catalogState->exclude($zone, (string) $node['catalog_key']);
            $this->reloadTrees($treeService);

            return;
        }

        if (($node['catalog_attachment'] ?? false) && filled($node['parent_catalog_key'] ?? null) && filled($node['_attachment_id'] ?? null)) {
            $catalogState->removeManualChild(
                $zone,
                (string) $node['parent_catalog_key'],
                (string) $node['_attachment_id'],
            );
            $this->reloadTrees($treeService);

            return;
        }

        $this->{$treeProp} = $this->removeAtPath($tree, $path);
        $treeService->syncZone($zone, $this->{$treeProp});
        $this->reloadTrees($treeService);
    }

    /**
     * @param  list<int>  $parentPath
     * @param  list<string>  $orderedKeys
     */
    public function syncNavigationSiblingOrder(
        string $zone,
        array $parentPath,
        array $orderedKeys,
        SiteNavigationTreeService $treeService,
        NavigationCatalogStateRepository $catalogState,
    ): void {
        $this->authorize('viewAny', Page::class);

        $parentPath = array_values(array_map(static fn ($v) => (int) $v, $parentPath));
        $orderedKeys = array_values(array_filter($orderedKeys, static fn ($key): bool => is_string($key) && $key !== ''));

        if ($orderedKeys === []) {
            return;
        }

        $treeProp = $zone === SiteNavigationItem::ZONE_FOOTER ? 'footerTree' : 'headerTree';
        $tree = $this->{$treeProp};
        $contextKey = $this->resolveSiblingContextKey($tree, $parentPath);

        if ($contextKey !== null) {
            $catalogState->setSiblingOrder($zone, $contextKey, $orderedKeys);
            $this->reloadTrees($treeService);

            return;
        }

        $this->{$treeProp} = $this->reorderSiblingsAtPath($tree, $parentPath, $orderedKeys);
        $treeService->syncZone($zone, $this->{$treeProp});
        $this->reloadTrees($treeService);
    }

    protected function reloadTrees(SiteNavigationTreeService $treeService): void
    {
        $this->headerTree = $treeService->serializeZone(SiteNavigationItem::ZONE_HEADER);
        $this->footerTree = $treeService->serializeZone(SiteNavigationItem::ZONE_FOOTER);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function buildNodeFromForm(): ?array
    {
        $type = $this->addType;

        if ($type === SiteNavigationItem::TYPE_GROUP) {
            if (trim($this->addTitle) === '') {
                $this->addError('addTitle', __('Enter a label for the menu group.'));

                return null;
            }

            return [
                'item_type' => $type,
                'title' => trim($this->addTitle),
                'children' => [],
            ];
        }

        if ($type === SiteNavigationItem::TYPE_PAGE) {
            if ($this->addPageId === null || $this->addPageId <= 0) {
                $this->addError('addPageId', __('Select a page.'));

                return null;
            }

            return [
                'item_type' => $type,
                'page_id' => $this->addPageId,
                'title' => trim($this->addTitle) !== '' ? trim($this->addTitle) : null,
                'children' => [],
            ];
        }

        if ($type === SiteNavigationItem::TYPE_CATEGORY) {
            if ($this->addCategoryId === null || $this->addCategoryId <= 0) {
                $this->addError('addCategoryId', __('Select a category.'));

                return null;
            }

            return [
                'item_type' => $type,
                'service_category_id' => $this->addCategoryId,
                'title' => trim($this->addTitle) !== '' ? trim($this->addTitle) : null,
                'children' => [],
            ];
        }

        if ($type === SiteNavigationItem::TYPE_SERVICE) {
            if ($this->addServiceId === null || $this->addServiceId <= 0) {
                $this->addError('addServiceId', __('Select a service.'));

                return null;
            }

            return [
                'item_type' => $type,
                'service_id' => $this->addServiceId,
                'title' => trim($this->addTitle) !== '' ? trim($this->addTitle) : null,
                'children' => [],
            ];
        }

        if ($type === SiteNavigationItem::TYPE_SUB_SERVICE) {
            if ($this->addSubServiceId === null || $this->addSubServiceId <= 0) {
                $this->addError('addSubServiceId', __('Select a sub-service.'));

                return null;
            }

            return [
                'item_type' => $type,
                'sub_service_id' => $this->addSubServiceId,
                'title' => trim($this->addTitle) !== '' ? trim($this->addTitle) : null,
                'children' => [],
            ];
        }

        if ($type === SiteNavigationItem::TYPE_URL) {
            if (trim($this->addCustomUrl) === '') {
                $this->addError('addCustomUrl', __('Enter a URL.'));

                return null;
            }

            return [
                'item_type' => $type,
                'custom_url' => trim($this->addCustomUrl),
                'title' => trim($this->addTitle) !== '' ? trim($this->addTitle) : null,
                'children' => [],
            ];
        }

        return null;
    }

    protected function resetAddForm(): void
    {
        $this->addTitle = '';
        $this->addCustomUrl = '';
        $this->addPageId = null;
        $this->addCategoryId = null;
        $this->addServiceId = null;
        $this->addSubServiceId = null;
        $this->addParentPath = [];
        $this->addParentCatalogKey = null;
        $this->editPath = null;
    }

    /**
     * @param  list<array<string, mixed>>  $tree
     * @param  list<int>  $path
     * @return array<string, mixed>|null
     */
    protected function nodeAtPath(array $tree, array $path): ?array
    {
        if ($path === []) {
            return null;
        }

        $index = (int) array_shift($path);
        if (! isset($tree[$index])) {
            return null;
        }

        $node = $tree[$index];

        if ($path === []) {
            return is_array($node) ? $node : null;
        }

        $children = is_array($node['children'] ?? null) ? $node['children'] : [];

        return $this->nodeAtPath($children, $path);
    }

    /**
     * @param  list<array<string, mixed>>  $tree
     * @param  list<int>  $path
     * @return list<array<string, mixed>>
     */
    protected function replaceAtPath(array $tree, array $path, array $node): array
    {
        if ($path === []) {
            return $tree;
        }

        if (count($path) === 1) {
            $index = (int) $path[0];
            if (! isset($tree[$index])) {
                return $tree;
            }

            $existingChildren = is_array($tree[$index]['children'] ?? null) ? $tree[$index]['children'] : [];
            $node['children'] = $existingChildren;
            $tree[$index] = $node;

            return $tree;
        }

        $index = (int) array_shift($path);
        if (! isset($tree[$index])) {
            return $tree;
        }

        $children = is_array($tree[$index]['children'] ?? null) ? $tree[$index]['children'] : [];
        $tree[$index]['children'] = $this->replaceAtPath($children, $path, $node);

        return $tree;
    }

    /**
     * @param  list<array<string, mixed>>  $tree
     * @param  list<int>  $parentPath
     */
    protected function resolveSiblingContextKey(array $tree, array $parentPath): ?string
    {
        if ($parentPath === []) {
            return null;
        }

        $parentNode = $this->nodeAtPath($tree, $parentPath);
        if ($parentNode === null) {
            return null;
        }

        if (($parentNode['auto_synced'] ?? false) && filled($parentNode['catalog_key'] ?? null)) {
            return (string) $parentNode['catalog_key'];
        }

        if ($this->isServicesAnchorNode($parentNode)) {
            return 'services';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected function isServicesAnchorNode(array $node): bool
    {
        if (($node['item_type'] ?? null) !== SiteNavigationItem::TYPE_PAGE) {
            return false;
        }

        $pageId = filled($node['page_id'] ?? null) ? (int) $node['page_id'] : null;
        if ($pageId === null) {
            return false;
        }

        $servicesPageId = Page::query()
            ->whereIn('slug', config('navigation.services_page_slugs', ['services']))
            ->where('is_active', true)
            ->value('id');

        return $servicesPageId !== null && (int) $servicesPageId === $pageId;
    }

    /**
     * @param  list<array<string, mixed>>  $tree
     * @param  list<int>  $parentPath
     * @param  list<string>  $orderedKeys
     * @return list<array<string, mixed>>
     */
    protected function reorderSiblingsAtPath(array $tree, array $parentPath, array $orderedKeys): array
    {
        if ($parentPath === []) {
            return $this->reorderSiblingNodes($tree, $orderedKeys);
        }

        $index = (int) array_shift($parentPath);
        if (! isset($tree[$index])) {
            return $tree;
        }

        $children = is_array($tree[$index]['children'] ?? null) ? $tree[$index]['children'] : [];
        $tree[$index]['children'] = $this->reorderSiblingsAtPath($children, $parentPath, $orderedKeys);

        return $tree;
    }

    /**
     * @param  list<array<string, mixed>>  $siblings
     * @param  list<string>  $orderedKeys
     * @return list<array<string, mixed>>
     */
    protected function reorderSiblingNodes(array $siblings, array $orderedKeys): array
    {
        $map = [];
        foreach ($siblings as $index => $node) {
            if (! is_array($node)) {
                continue;
            }
            $map[$this->navigationNodeKey($node, $index)] = $node;
        }

        $reordered = [];
        foreach ($orderedKeys as $key) {
            if (isset($map[$key])) {
                $reordered[] = $map[$key];
                unset($map[$key]);
            }
        }

        foreach ($map as $node) {
            $reordered[] = $node;
        }

        return array_values($reordered);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected function navigationNodeKey(array $node, int $index): string
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

        return 'manual:idx:'.$index;
    }

    /**
     * @param  list<array<string, mixed>>  $tree
     * @param  list<int>  $path
     * @param  array<string, mixed>  $node
     * @return list<array<string, mixed>>
     */
    protected function insertChildAtPath(array $tree, array $path, array $node): array
    {
        if ($path === []) {
            $tree[] = $node;

            return $tree;
        }

        $index = (int) array_shift($path);
        if (! isset($tree[$index])) {
            return $tree;
        }

        $children = is_array($tree[$index]['children'] ?? null) ? $tree[$index]['children'] : [];
        $tree[$index]['children'] = $this->insertChildAtPath($children, $path, $node);

        return $tree;
    }

    /**
     * @param  list<array<string, mixed>>  $tree
     * @param  list<int>  $path
     * @return list<array<string, mixed>>
     */
    protected function removeAtPath(array $tree, array $path): array
    {
        if ($path === []) {
            return $tree;
        }

        if (count($path) === 1) {
            $index = (int) $path[0];
            unset($tree[$index]);

            return array_values($tree);
        }

        $index = (int) array_shift($path);
        if (! isset($tree[$index])) {
            return $tree;
        }

        $children = is_array($tree[$index]['children'] ?? null) ? $tree[$index]['children'] : [];
        $tree[$index]['children'] = $this->removeAtPath($children, $path);

        return $tree;
    }

    /**
     * @return Collection<int, Page>
     */
    protected function poolPages(): Collection
    {
        $term = trim($this->poolSearch);
        if (mb_strlen($term) < 2) {
            return collect();
        }

        return Page::query()
            ->where('is_active', true)
            ->where(function ($q) use ($term): void {
                $q->where('title', 'like', '%'.$term.'%')
                    ->orWhere('slug', 'like', '%'.$term.'%');
            })
            ->orderBy('title')
            ->limit(40)
            ->get(['id', 'title', 'slug']);
    }

    /**
     * @return Collection<int, ServiceCategory>
     */
    protected function categoryOptions(): Collection
    {
        if (! Schema::hasTable('service_categories')) {
            return collect();
        }

        $term = trim($this->poolSearch);

        return ServiceCategory::query()
            ->where('is_active', true)
            ->when($term !== '', fn ($q) => $q->where(function ($inner) use ($term): void {
                $inner->where('name', 'like', '%'.$term.'%')
                    ->orWhere('code', 'like', '%'.$term.'%');
            }))
            ->orderBy('name')
            ->limit(40)
            ->get(['id', 'name', 'code']);
    }

    /**
     * @return Collection<int, Service>
     */
    protected function serviceOptions(): Collection
    {
        $term = trim($this->poolSearch);

        return Service::query()
            ->where('is_active', true)
            ->when($term !== '', fn ($q) => $q->where(function ($inner) use ($term): void {
                $inner->where('title', 'like', '%'.$term.'%')
                    ->orWhere('service_code', 'like', '%'.$term.'%');
            }))
            ->orderBy('title')
            ->limit(40)
            ->get(['id', 'title', 'service_code']);
    }

    /**
     * @return Collection<int, SubService>
     */
    protected function subServiceOptions(): Collection
    {
        if (! Schema::hasTable('sub_services')) {
            return collect();
        }

        $term = trim($this->poolSearch);

        return SubService::query()
            ->where('is_active', true)
            ->with('service:id,title,service_code')
            ->when($term !== '', fn ($q) => $q->where(function ($inner) use ($term): void {
                $inner->where('title', 'like', '%'.$term.'%')
                    ->orWhere('sub_service_code', 'like', '%'.$term.'%');
            }))
            ->orderBy('title')
            ->limit(40)
            ->get(['id', 'service_id', 'sub_service_code', 'title']);
    }
}
