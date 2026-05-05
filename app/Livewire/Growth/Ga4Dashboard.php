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

    /** @var array<string, mixed> */
    public array $ga4Bundle = [];

    public function mount(): void
    {
        $this->authorize('view', MarketingSetting::current());
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
        $this->ga4Bundle = app(Ga4DataApiService::class)->fetchReportBundle(MarketingSetting::current());
    }
}
