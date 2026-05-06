<?php

namespace App\Livewire\Growth;

use App\Models\MarketingSetting;
use App\Services\Marketing\Ga4DataApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

class Ga4Dashboard extends Component
{
    use AuthorizesRequests;

    public ?string $flash = null;

    /** 7d · 28d · 90d — matches Ga4DataApiService::RANGE_PRESETS */
    public string $rangePreset = '28d';

    /** @var array<string, mixed> */
    public array $ga4Bundle = [];

    public function mount(): void
    {
        $this->authorize('view', MarketingSetting::current());
        $this->rangePreset = in_array($this->rangePreset, Ga4DataApiService::RANGE_PRESETS, true)
            ? $this->rangePreset
            : '28d';
        $this->loadReports();
    }

    public function updatedRangePreset(string $value): void
    {
        $this->rangePreset = in_array($value, Ga4DataApiService::RANGE_PRESETS, true) ? $value : '28d';
        $this->loadReports();
    }

    public function refreshData(): void
    {
        Ga4DataApiService::forgetCache(MarketingSetting::current());
        $this->loadReports();
        $this->flash = __('Reports refreshed.');
    }

    public function render(): View
    {
        return view('livewire.growth.ga4-dashboard', [
            'ga4DashboardUrl' => config('marketing.ga4_dashboard_url'),
        ]);
    }

    protected function loadReports(): void
    {
        $this->ga4Bundle = app(Ga4DataApiService::class)->fetchReportBundle(
            MarketingSetting::current(),
            $this->rangePreset
        );
    }
}
