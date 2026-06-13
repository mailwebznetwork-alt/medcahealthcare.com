<?php

namespace App\Livewire\Operations\Services;

use App\Livewire\Concerns\InteractsWithBulkActions;
use App\Models\Service;
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

    #[Url(as: 'publish_status', history: true, keep: true)]
    public string $publishStatus = '';

    #[Url(as: 'active', history: true, keep: true)]
    public string $active = '';

    #[Url(as: 'featured', history: true, keep: true)]
    public string $featured = '';

    #[Url(as: 'category_id', history: true, keep: true)]
    public string $categoryId = '';

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('viewAny', Service::class);

        if ($this->categoryId === '' && request()->has('category_ids')) {
            $legacy = request()->query('category_ids');
            $this->categoryId = (string) (is_array($legacy) ? ($legacy[0] ?? '') : $legacy);
        }
    }

    public function bulkResourceKey(): string
    {
        return 'operations.services';
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingPublishStatus(): void
    {
        $this->resetPage();
    }

    public function updatingActive(): void
    {
        $this->resetPage();
    }

    public function updatingFeatured(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    protected function bulkFilteredIdsQuery(): Builder
    {
        return $this->filteredQuery();
    }

    private function filteredQuery(): Builder
    {
        $query = Service::query()
            ->with('categories')
            ->withCount(['subServices', 'pincodes'])
            ->orderByDesc('updated_at');

        if ($term = trim($this->q)) {
            $query->where(function ($q) use ($term): void {
                $q->where('title', 'like', '%'.$term.'%')
                    ->orWhere('service_code', 'like', '%'.$term.'%');
            });
        }

        if ($this->publishStatus !== '') {
            $query->where('publish_status', $this->publishStatus);
        }

        if ($this->active !== '') {
            $query->where('is_active', $this->active === '1');
        }

        if ($this->featured !== '') {
            $query->where('is_featured', $this->featured === '1');
        }

        if ($this->categoryId !== '') {
            $query->inCategories([(int) $this->categoryId]);
        }

        return $query;
    }

    public function render(ServiceCategoryRepository $categoryRepository): View
    {
        return view('livewire.operations.services.index', [
            'services' => $this->filteredQuery()->paginate(20),
            'categoryOptions' => $categoryRepository->activeForPicker(),
        ]);
    }
}
