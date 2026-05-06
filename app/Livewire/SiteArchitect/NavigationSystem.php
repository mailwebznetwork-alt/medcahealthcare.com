<?php

namespace App\Livewire\SiteArchitect;

use App\Models\Page;
use App\Models\SiteNavigationItem;
use App\Services\ActivityLogService;
use App\Services\Growth\AiPulseService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class NavigationSystem extends Component
{
    use AuthorizesRequests;

    /** @var list<int> */
    public array $headerIds = [];

    /** @var list<int> */
    public array $footerIds = [];

    /**
     * Menu label overrides keyed by page id (string keys for Livewire).
     *
     * @var array<string, string>
     */
    public array $customLabels = [];

    public ?string $lastSavedAt = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Page::class);
        $this->pruneStaleNavigationItems();
    }

    public function render(): View
    {
        return view('livewire.site-architect.navigation-system', [
            'poolPages' => $this->poolPages(),
            'headerPages' => $this->pagesForIds($this->headerIds),
            'footerPages' => $this->pagesForIds($this->footerIds),
            'livePageCount' => Page::query()->where('is_active', true)->count(),
        ]);
    }

    /**
     * Persists label overrides after an input blur (no navigation_menu_update audit row).
     */
    public function saveNavigationLabels(): void
    {
        $this->authorize('viewAny', Page::class);
        $this->persistMenus(logAudit: false);
    }

    /**
     * Called from SortableJS after drag release; persists header/footer menus.
     *
     * @param  list<int|string>  $headerIds
     * @param  list<int|string>  $footerIds
     */
    public function syncFromDrag(array $headerIds, array $footerIds): void
    {
        $this->authorize('viewAny', Page::class);

        $this->headerIds = $this->validatedOrderedPageIds($headerIds);
        $this->footerIds = $this->validatedOrderedPageIds($footerIds);
        $this->footerIds = array_values(array_diff($this->footerIds, $this->headerIds));

        $this->pruneCustomLabelsNotInMenus();
        $this->ensureCustomLabelKeysForMenus();

        $this->persistMenus(logAudit: true);
    }

    protected function loadMenuIdsFromDatabase(): void
    {
        if (! Schema::hasTable('site_navigation_items')) {
            $this->headerIds = [];
            $this->footerIds = [];

            return;
        }

        $this->headerIds = SiteNavigationItem::query()
            ->where('zone', SiteNavigationItem::ZONE_HEADER)
            ->orderBy('sort_order')
            ->pluck('page_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->footerIds = SiteNavigationItem::query()
            ->where('zone', SiteNavigationItem::ZONE_FOOTER)
            ->orderBy('sort_order')
            ->pluck('page_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function loadCustomLabelsFromDatabase(): void
    {
        if (! Schema::hasTable('site_navigation_items')) {
            return;
        }

        if (! Schema::hasColumn('site_navigation_items', 'custom_label')) {
            return;
        }

        $rows = SiteNavigationItem::query()->get(['page_id', 'custom_label']);

        foreach ($rows as $row) {
            $label = $row->custom_label;
            if ($label !== null && trim((string) $label) !== '') {
                $this->customLabels[(string) $row->page_id] = (string) $label;
            }
        }
    }

    protected function ensureCustomLabelKeysForMenus(): void
    {
        foreach (array_merge($this->headerIds, $this->footerIds) as $id) {
            $key = (string) $id;
            if (! array_key_exists($key, $this->customLabels)) {
                $this->customLabels[$key] = '';
            }
        }
    }

    protected function pruneCustomLabelsNotInMenus(): void
    {
        $allowed = array_flip(array_merge($this->headerIds, $this->footerIds));
        foreach (array_keys($this->customLabels) as $key) {
            if (! isset($allowed[(int) $key])) {
                unset($this->customLabels[$key]);
            }
        }
    }

    /**
     * Remove DB rows that point at inactive or missing pages, then reload menu state.
     */
    protected function pruneStaleNavigationItems(): void
    {
        if (! Schema::hasTable('site_navigation_items')) {
            $this->headerIds = [];
            $this->footerIds = [];

            return;
        }

        $activeIds = Page::query()->where('is_active', true)->pluck('id');

        if ($activeIds->isEmpty()) {
            SiteNavigationItem::query()->delete();
        } else {
            SiteNavigationItem::query()->whereNotIn('page_id', $activeIds->all())->delete();
        }

        SiteNavigationItem::query()->whereDoesntHave('page')->delete();

        $this->loadMenuIdsFromDatabase();
        $this->loadCustomLabelsFromDatabase();
        $this->ensureCustomLabelKeysForMenus();
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    protected function validatedOrderedPageIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map(fn ($v) => (int) $v, $ids)));
        if ($ids === []) {
            return [];
        }

        $valid = Page::query()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $validSet = array_flip($valid);

        $out = [];
        foreach ($ids as $id) {
            if (isset($validSet[$id])) {
                $out[] = $id;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return Collection<int, Page>
     */
    protected function poolPages(): Collection
    {
        $used = array_merge($this->headerIds, $this->footerIds);

        return Page::query()
            ->where('is_active', true)
            ->when($used !== [], fn ($q) => $q->whereNotIn('id', $used))
            ->orderBy('title')
            ->get();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Page>
     */
    protected function pagesForIds(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $pages = Page::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $pages->get($id))
            ->filter()
            ->values();
    }

    protected function persistMenus(bool $logAudit = true): void
    {
        if (! Schema::hasTable('site_navigation_items')) {
            return;
        }

        DB::transaction(function (): void {
            SiteNavigationItem::query()->where('zone', SiteNavigationItem::ZONE_HEADER)->delete();
            SiteNavigationItem::query()->where('zone', SiteNavigationItem::ZONE_FOOTER)->delete();

            $hasCustomLabelColumn = Schema::hasColumn('site_navigation_items', 'custom_label');

            foreach ($this->headerIds as $order => $pageId) {
                $payload = [
                    'zone' => SiteNavigationItem::ZONE_HEADER,
                    'page_id' => $pageId,
                    'sort_order' => $order,
                ];
                if ($hasCustomLabelColumn) {
                    $payload['custom_label'] = $this->normalizedCustomLabel($pageId);
                }
                SiteNavigationItem::query()->create($payload);
            }

            foreach ($this->footerIds as $order => $pageId) {
                $payload = [
                    'zone' => SiteNavigationItem::ZONE_FOOTER,
                    'page_id' => $pageId,
                    'sort_order' => $order,
                ];
                if ($hasCustomLabelColumn) {
                    $payload['custom_label'] = $this->normalizedCustomLabel($pageId);
                }
                SiteNavigationItem::query()->create($payload);
            }
        });

        $this->lastSavedAt = Carbon::now()->timezone(config('app.timezone'))->format('M j, Y g:i A');

        if ($logAudit) {
            app(ActivityLogService::class)->log(
                'navigation_menu_update',
                'site_architect',
                'Header: '.implode(',', $this->headerIds).' · Footer: '.implode(',', $this->footerIds)
            );
        }

        app(AiPulseService::class)->triggerAuditAfterPublish();
    }

    protected function normalizedCustomLabel(int $pageId): ?string
    {
        $raw = $this->customLabels[(string) $pageId] ?? null;
        if ($raw === null || trim((string) $raw) === '') {
            return null;
        }

        return mb_substr(trim((string) $raw), 0, 255);
    }
}
