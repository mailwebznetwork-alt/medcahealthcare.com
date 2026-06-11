<?php

namespace App\Livewire\System;

use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Governance\SourceOfTruthDashboardService;
use App\Services\Governance\SourceOfTruthListService;
use App\Services\Governance\UniversalPageRegistry;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SourceOfTruth extends Component
{
    use WithPagination;

    public ?string $flash = null;

    public string $flashType = 'success';

    /** @var array<string, mixed> */
    public array $report = [];

    public ?string $list = null;

    protected $queryString = [
        'list' => ['except' => null],
    ];

    public function mount(SourceOfTruthDashboardService $dashboard): void
    {
        $this->list = request()->query('list');

        if ($this->list !== null && ! SourceOfTruthListService::supports($this->list)) {
            $this->redirectRoute('system.source-of-truth', navigate: true);

            return;
        }

        if ($this->list === null) {
            $this->refreshReport($dashboard);
        }
    }

    public function refreshReport(SourceOfTruthDashboardService $dashboard): void
    {
        $this->report = $dashboard->report();
    }

    public function syncRegistry(UniversalPageRegistry $registry, SourceOfTruthDashboardService $dashboard): void
    {
        $counts = $registry->syncAll();

        $this->flashType = 'success';
        $this->flash = __('Registry synced — :count entries updated.', [
            'count' => number_format((int) ($counts['synced'] ?? 0)),
        ]);

        $this->refreshReport($dashboard);
    }

    public function purgeOrphans(DownstreamArtifactPurger $purger, SourceOfTruthDashboardService $dashboard): void
    {
        $result = $purger->purgeAllCatalogOrphans();
        $totalRemoved = (int) ($result['registry_removed'] ?? 0)
            + (int) ($result['location_pages_removed'] ?? 0)
            + (int) ($result['service_pages_removed'] ?? 0)
            + (int) ($result['sub_service_pages_removed'] ?? 0)
            + (int) ($result['category_pages_removed'] ?? 0);

        $this->flashType = $totalRemoved > 0 ? 'success' : 'info';
        $this->flash = $totalRemoved > 0
            ? __('Removed :registry registry, :location location, :service service, :sub sub-service, and :category category orphan(s).', [
                'registry' => number_format((int) ($result['registry_removed'] ?? 0)),
                'location' => number_format((int) ($result['location_pages_removed'] ?? 0)),
                'service' => number_format((int) ($result['service_pages_removed'] ?? 0)),
                'sub' => number_format((int) ($result['sub_service_pages_removed'] ?? 0)),
                'category' => number_format((int) ($result['category_pages_removed'] ?? 0)),
            ])
            : __('No orphan registry rows or catalog pages found.');

        $this->refreshReport($dashboard);
    }

    public function isListMode(): bool
    {
        return $this->list !== null && SourceOfTruthListService::supports($this->list);
    }

    public function render(
        SourceOfTruthDashboardService $dashboard,
        SourceOfTruthListService $lists,
    ): View {
        if ($this->isListMode()) {
            return view('livewire.system.source-of-truth', [
                'listRows' => $lists->paginate($this->list),
                'listLabel' => $lists->label($this->list),
                'listColumns' => $lists->columns($this->list),
                'listKey' => $this->list,
            ]);
        }

        if ($this->report === []) {
            $this->report = $dashboard->report();
        }

        return view('livewire.system.source-of-truth');
    }
}
