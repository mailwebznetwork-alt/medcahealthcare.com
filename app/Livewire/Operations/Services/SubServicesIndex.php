<?php

namespace App\Livewire\Operations\Services;

use App\Livewire\Concerns\InteractsWithBulkActions;
use App\Models\Service;
use App\Models\SubService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SubServicesIndex extends Component
{
    use AuthorizesRequests;
    use InteractsWithBulkActions;
    use WithPagination;

    public Service $service;

    public function mount(Service $service): void
    {
        $this->service = $service;
        $this->authorize('update', $service);
    }

    public function bulkResourceKey(): string
    {
        return 'operations.sub_services';
    }

    protected function bulkFilteredIdsQuery(): Builder
    {
        return SubService::query()
            ->where('service_id', $this->service->id)
            ->orderBy('sort_order');
    }

    public function render(): View
    {
        $subServices = SubService::query()
            ->where('service_id', $this->service->id)
            ->with(['seo', 'linkedPage'])
            ->ordered()
            ->get();

        return view('livewire.operations.services.sub-services-index', [
            'subServices' => $subServices,
        ]);
    }
}
