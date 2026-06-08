<?php

namespace App\Livewire\Operations\JobPortal;

use App\Livewire\Concerns\InteractsWithBulkActions;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class VacanciesIndex extends Component
{
    use AuthorizesRequests;
    use InteractsWithBulkActions;
    use WithPagination;

    #[Url(as: 'q', history: true, keep: true)]
    public string $q = '';

    #[Url(as: 'workflow_status', history: true, keep: true)]
    public string $workflowStatus = '';

    #[Url(as: 'visibility', history: true, keep: true)]
    public string $visibility = '';

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('viewAny', Vacancy::class);
    }

    public function bulkResourceKey(): string
    {
        return 'operations.vacancies';
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingWorkflowStatus(): void
    {
        $this->resetPage();
    }

    public function updatingVisibility(): void
    {
        $this->resetPage();
    }

    protected function bulkFilteredIdsQuery(): Builder
    {
        return $this->filteredQuery();
    }

    private function filteredQuery(): Builder
    {
        $query = Vacancy::query()->orderBy('sort_order')->orderByDesc('updated_at');

        if ($term = trim($this->q)) {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('title', 'like', $like)
                    ->orWhere('department', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('pin_code', 'like', $like);
            });
        }

        if ($this->workflowStatus !== '') {
            $query->where('workflow_status', $this->workflowStatus);
        }

        if ($this->visibility !== '') {
            $query->where('visibility', $this->visibility);
        }

        return $query;
    }

    public function render(): View
    {
        return view('livewire.operations.job-portal.vacancies-index', [
            'vacancies' => $this->filteredQuery()->paginate(15),
        ]);
    }
}
