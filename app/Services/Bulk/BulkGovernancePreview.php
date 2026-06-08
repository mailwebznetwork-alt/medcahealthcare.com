<?php

namespace App\Services\Bulk;

use App\Models\Block;
use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use Illuminate\Support\Collection;

/**
 * Read-only impact preview for destructive bulk actions (does not mutate governance).
 */
class BulkGovernancePreview
{
    /**
     * @param  list<int>  $ids
     * @return array<string, mixed>
     */
    public function forPages(array $ids, string $action): array
    {
        $pages = Page::query()->whereIn('id', $ids)->get(['id', 'slug', 'title', 'page_category']);

        $registryRows = PageRegistry::query()
            ->whereIn('page_id', $ids)
            ->get(['registry_key', 'entity_type', 'page_id']);

        $locationPages = ServiceLocationPage::query()
            ->whereIn('page_id', $ids)
            ->with(['service:id,service_code,title', 'pincode:id,pincode,area_name'])
            ->get();

        $serviceDetailPages = Service::query()
            ->whereIn('detail_page_id', $ids)
            ->get(['id', 'service_code', 'title', 'detail_page_id']);

        $urls = $pages->map(function (Page $page) use ($locationPages, $serviceDetailPages): array {
            $loc = $locationPages->firstWhere('page_id', $page->id);
            $svc = $serviceDetailPages->firstWhere('detail_page_id', $page->id);

            return [
                'page_id' => $page->id,
                'slug' => $page->slug,
                'url' => $loc?->publicUrl() ?? ($svc ? '/services/'.$svc->service_code : '/p/'.$page->slug),
            ];
        })->values()->all();

        return [
            'selected_count' => count($ids),
            'action' => $action,
            'affected_pages' => $pages->map(fn (Page $p) => $p->title.' ('.$p->slug.')')->values()->all(),
            'affected_registry_rows' => $registryRows->pluck('registry_key')->values()->all(),
            'affected_mappings' => $locationPages->count() + $serviceDetailPages->count(),
            'affected_urls' => collect($urls)->pluck('url')->values()->all(),
            'affected_location_pages' => $locationPages->map(fn ($row) => ($row->service?->title ?? '—').' · '.($row->pincode?->area_name ?? $row->pincode?->pincode ?? '—'))->values()->all(),
            'affected_service_pages' => $serviceDetailPages->map(fn (Service $s) => $s->title.' ('.$s->service_code.')')->values()->all(),
            'cascading_deletions' => $this->cascadingSummary($pages, $locationPages, $serviceDetailPages, $registryRows),
            'requires_delete_confirmation' => in_array($action, ['delete'], true),
        ];
    }

    /**
     * @param  list<int>  $ids
     * @return array<string, mixed>
     */
    public function forBlocks(array $ids, string $action): array
    {
        $blocks = Block::query()->whereIn('id', $ids)->get(['id', 'block_slug', 'block_name', 'is_managed']);

        return [
            'selected_count' => count($ids),
            'action' => $action,
            'affected_pages' => [],
            'affected_registry_rows' => [],
            'affected_mappings' => 0,
            'affected_urls' => [],
            'affected_location_pages' => [],
            'affected_service_pages' => [],
            'cascading_deletions' => $blocks->map(fn (Block $b) => __('Block :slug will be removed from the registry.', ['slug' => $b->block_slug]))->values()->all(),
            'requires_delete_confirmation' => in_array($action, ['delete'], true),
        ];
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @param  Collection<int, ServiceLocationPage>  $locationPages
     * @param  Collection<int, Service>  $serviceDetailPages
     * @param  Collection<int, PageRegistry>  $registryRows
     * @return list<string>
     */
    private function cascadingSummary(
        Collection $pages,
        Collection $locationPages,
        Collection $serviceDetailPages,
        Collection $registryRows,
    ): array {
        $lines = [];

        if ($registryRows->isNotEmpty()) {
            $lines[] = __(':count universal registry row(s) will be purged.', ['count' => $registryRows->count()]);
        }

        if ($locationPages->isNotEmpty()) {
            $lines[] = __(':count service location mapping(s) may be affected.', ['count' => $locationPages->count()]);
        }

        if ($serviceDetailPages->isNotEmpty()) {
            $lines[] = __(':count service detail page link(s) reference selected pages.', ['count' => $serviceDetailPages->count()]);
        }

        if ($lines === []) {
            $lines[] = __('Selected pages will be removed from Site Architect. Downstream artifacts follow existing purge rules.');
        }

        return $lines;
    }
}
