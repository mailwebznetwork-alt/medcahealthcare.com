<?php

namespace App\Livewire\System;

use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Governance\SourceOfTruthDashboardService;
use App\Services\Governance\UniversalPageRegistry;
use Illuminate\View\View;
use Livewire\Component;

class SourceOfTruth extends Component
{
    public ?string $flash = null;

    public string $flashType = 'success';

    /** @var array<string, mixed> */
    public array $report = [];

    public function mount(SourceOfTruthDashboardService $dashboard): void
    {
        $this->refreshReport($dashboard);
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
        $result = $purger->purgeRegistryOrphans();
        $removed = (int) ($result['registry_removed'] ?? 0);

        $this->flashType = $removed > 0 ? 'success' : 'info';
        $this->flash = $removed > 0
            ? __('Removed :count orphan registry row(s).', ['count' => number_format($removed)])
            : __('No orphan registry rows found.');

        $this->refreshReport($dashboard);
    }

    public function render(): View
    {
        return view('livewire.system.source-of-truth');
    }
}
