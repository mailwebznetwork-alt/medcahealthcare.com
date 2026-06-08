<?php

namespace App\Livewire\Operations\PinCodes;

use App\Livewire\Concerns\InteractsWithBulkActions;
use App\Models\PinCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Directory extends Component
{
    use AuthorizesRequests;
    use InteractsWithBulkActions;
    use WithPagination;

    #[Url(as: 'q', history: true, keep: true)]
    public string $q = '';

    #[Url(as: 'city', history: true, keep: true)]
    public string $city = '';

    #[Url(as: 'serviceable', history: true, keep: true)]
    public string $serviceable = '';

    #[Url(as: 'active', history: true, keep: true)]
    public string $active = '';

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('viewAny', PinCode::class);
    }

    public function bulkResourceKey(): string
    {
        return 'operations.pin_codes';
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingCity(): void
    {
        $this->resetPage();
    }

    public function updatingServiceable(): void
    {
        $this->resetPage();
    }

    public function updatingActive(): void
    {
        $this->resetPage();
    }

    protected function bulkFilteredIdsQuery(): Builder
    {
        return $this->filteredQuery();
    }

    private function filteredQuery(): Builder
    {
        $query = PinCode::query()->orderBy('city')->orderBy('pincode');

        if ($search = trim($this->q)) {
            $like = '%'.$search.'%';
            $query->where(function ($sub) use ($like): void {
                $sub->where('pincode', 'like', $like)
                    ->orWhere('area_name', 'like', $like)
                    ->orWhere('locality', 'like', $like)
                    ->orWhere('city', 'like', $like);
            });
        }

        if ($this->city !== '') {
            $query->where('city', $this->city);
        }

        if ($this->serviceable === '1') {
            $query->where('is_serviceable', true);
        } elseif ($this->serviceable === '0') {
            $query->where('is_serviceable', false);
        }

        if ($this->active === '1') {
            $query->where('is_active', true);
        } elseif ($this->active === '0') {
            $query->where('is_active', false);
        }

        return $query;
    }

    public function render(): View
    {
        $pinCodes = $this->filteredQuery()->paginate(20);

        $cities = PinCode::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return view('livewire.operations.pin-codes.directory', [
            'pinCodes' => $pinCodes,
            'cities' => $cities,
        ]);
    }
}
