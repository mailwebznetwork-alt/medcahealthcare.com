<?php

namespace App\Livewire\Operations\JobPortal;

use App\Livewire\Concerns\InteractsWithBulkActions;
use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ApplicationsIndex extends Component
{
    use AuthorizesRequests;
    use InteractsWithBulkActions;
    use WithPagination;

    #[Url(as: 'vacancy_id', history: true, keep: true)]
    public string $vacancyId = '';

    #[Url(as: 'pipeline_status', history: true, keep: true)]
    public string $pipelineStatus = '';

    #[Url(as: 'q', history: true, keep: true)]
    public string $q = '';

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('viewAny', Application::class);
    }

    public function bulkResourceKey(): string
    {
        return 'operations.applications';
    }

    public function updatingVacancyId(): void
    {
        $this->resetPage();
    }

    public function updatingPipelineStatus(): void
    {
        $this->resetPage();
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    protected function bulkFilteredIdsQuery(): Builder
    {
        return $this->filteredQuery();
    }

    private function filteredQuery(): Builder
    {
        $query = Application::query()->with('vacancy')->orderByDesc('created_at');

        if ($this->vacancyId !== '' && is_numeric($this->vacancyId)) {
            $query->where('vacancy_id', (int) $this->vacancyId);
        }

        if ($this->pipelineStatus !== '') {
            $query->where('pipeline_status', $this->pipelineStatus);
        }

        if ($term = trim($this->q)) {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('full_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        return $query;
    }

    public function render(): View
    {
        return view('livewire.operations.job-portal.applications-index', [
            'applications' => $this->filteredQuery()->paginate(20),
            'vacancies' => Vacancy::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }
}
