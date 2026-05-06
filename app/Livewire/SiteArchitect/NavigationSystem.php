<?php

namespace App\Livewire\SiteArchitect;

use App\Models\Page;
use App\Models\SiteNavigationItem;
use App\Services\ActivityLogService;
use App\Services\Growth\AiPulseService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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

    public function mount(): void
    {
        $this->authorize('viewAny', Page::class);
        $this->loadMenuIdsFromDatabase();
    }

    public function render(): View
    {
        return view('livewire.site-architect.navigation-system', [
            'poolPages' => $this->poolPages(),
            'headerPages' => $this->pagesForIds($this->headerIds),
            'footerPages' => $this->pagesForIds($this->footerIds),
        ]);
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

        $this->persistMenus();
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

    protected function persistMenus(): void
    {
        DB::transaction(function (): void {
            SiteNavigationItem::query()->where('zone', SiteNavigationItem::ZONE_HEADER)->delete();
            SiteNavigationItem::query()->where('zone', SiteNavigationItem::ZONE_FOOTER)->delete();

            foreach ($this->headerIds as $order => $pageId) {
                SiteNavigationItem::query()->create([
                    'zone' => SiteNavigationItem::ZONE_HEADER,
                    'page_id' => $pageId,
                    'sort_order' => $order,
                ]);
            }

            foreach ($this->footerIds as $order => $pageId) {
                SiteNavigationItem::query()->create([
                    'zone' => SiteNavigationItem::ZONE_FOOTER,
                    'page_id' => $pageId,
                    'sort_order' => $order,
                ]);
            }
        });

        app(ActivityLogService::class)->log(
            'navigation_menu_update',
            'site_architect',
            'Header: '.implode(',', $this->headerIds).' · Footer: '.implode(',', $this->footerIds)
        );

        app(AiPulseService::class)->triggerAuditAfterPublish();
    }
}
