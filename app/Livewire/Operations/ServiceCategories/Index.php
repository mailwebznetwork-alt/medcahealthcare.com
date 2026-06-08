<?php

namespace App\Livewire\Operations\ServiceCategories;

use App\Livewire\Concerns\InteractsWithBulkActions;
use App\Models\ServiceCategory;
use App\Repositories\Operations\ServiceCategoryRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use InteractsWithBulkActions;
    use WithPagination;

    #[Url(as: 'q', history: true, keep: true)]
    public string $q = '';

    #[Url(as: 'active', history: true, keep: true)]
    public string $active = '';

    #[Url(as: 'parent_id', history: true, keep: true)]
    public string $parentId = '';

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('viewAny', ServiceCategory::class);
    }

    public function bulkResourceKey(): string
    {
        return 'operations.service_categories';
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingActive(): void
    {
        $this->resetPage();
    }

    public function updatingParentId(): void
    {
        $this->resetPage();
    }

    protected function bulkFilteredIdsQuery(): Builder
    {
        return $this->filteredQuery();
    }

    private function filteredQuery(): Builder
    {
        $query = ServiceCategory::query()
            ->with(['parent:id,name,code'])
            ->withCount('services')
            ->ordered();

        if ($term = trim($this->q)) {
            $query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('code', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%');
            });
        }

        if ($this->active !== '') {
            $query->where('is_active', $this->active === '1');
        }

        if ($this->parentId !== '') {
            if ($this->parentId === '0') {
                $query->whereNull('parent_id');
            } elseif (is_numeric($this->parentId)) {
                $query->where('parent_id', (int) $this->parentId);
            }
        }

        return $query;
    }

    public function render(ServiceCategoryRepository $categoryRepository): View
    {
        return view('livewire.operations.service-categories.index', [
            'categories' => $this->filteredQuery()->paginate(20),
            'parentOptions' => $categoryRepository->parentOptions(),
        ]);
    }
}
