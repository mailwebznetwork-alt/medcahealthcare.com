<?php

namespace App\Livewire\Growth;

use App\ModuleAccess;
use App\Services\Growth\AiPulseService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AiPulsePanel extends Component
{
    /** @var array<string, mixed> */
    public array $snapshot = [];

    public ?string $flash = null;

    public string $flashType = 'success';

    public function mount(AiPulseService $pulse): void
    {
        abort_unless(Auth::user()?->hasModuleAccess(ModuleAccess::GROWTH_CENTER) ?? false, 403);
        $this->snapshot = $pulse->cachedSnapshotOrDispatch(false);
    }

    public function runDeepScan(AiPulseService $pulse): void
    {
        abort_unless(Auth::user()?->hasModuleAccess(ModuleAccess::GROWTH_CENTER) ?? false, 403);
        $this->snapshot = $pulse->rebuildAndRead(true);
        $this->flash = __('Deep scan complete.');
        $this->flashType = 'success';
    }

    public function refreshSnapshot(AiPulseService $pulse): void
    {
        abort_unless(Auth::user()?->hasModuleAccess(ModuleAccess::GROWTH_CENTER) ?? false, 403);
        $this->snapshot = $pulse->rebuildAndRead(false);
        $this->flash = __('Snapshot refreshed.');
        $this->flashType = 'success';
    }

    public function fixLink(mixed $scope, mixed $id, mixed $url, AiPulseService $pulse): void
    {
        abort_unless(Auth::user()?->hasModuleAccess(ModuleAccess::GROWTH_CENTER) ?? false, 403);
        $scope = (string) $scope;
        $id = (int) $id;
        $url = (string) $url;
        if (! in_array($scope, ['page', 'blog', 'block'], true)) {
            $this->flash = __('Invalid scope.');
            $this->flashType = 'error';

            return;
        }
        $result = $pulse->fixWithAi($scope, $id, $url);
        $this->flash = $result['message'];
        $this->flashType = $result['success'] ? 'success' : 'error';
        $this->snapshot = $pulse->rebuildAndRead();
    }

    public function render(): View
    {
        return view('livewire.growth.ai-pulse-panel');
    }
}
