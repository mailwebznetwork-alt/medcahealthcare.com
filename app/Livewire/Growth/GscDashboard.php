<?php

namespace App\Livewire\Growth;

use App\Models\MarketingSetting;
use App\Services\Growth\GoogleSearchConsoleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

class GscDashboard extends Component
{
    use AuthorizesRequests;

    public ?string $flash = null;

    public int $days = 28;

    /** @var array{configured: bool, sites: list<string>, error: ?string} */
    public array $connection = ['configured' => false, 'sites' => [], 'error' => null];

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public ?string $queryError = null;

    public function mount(): void
    {
        $this->authorize('view', MarketingSetting::current());
        $this->testConnection();
    }

    public function testConnection(): void
    {
        $this->connection = app(GoogleSearchConsoleService::class)->testConnection();
        $this->flash = $this->connection['error'] === null && $this->connection['configured']
            ? __('GSC connection OK.')
            : null;
    }

    public function loadAnalytics(): void
    {
        $site = config('growth.google_search_console.site_url', config('app.url'));
        $result = app(GoogleSearchConsoleService::class)->searchAnalytics($site, $this->days);
        $this->rows = $result['rows'];
        $this->queryError = $result['error'];
        $this->flash = $this->queryError === null ? __('Search analytics loaded.') : null;
    }

    public function render(): View
    {
        return view('livewire.growth.gsc-dashboard');
    }
}
