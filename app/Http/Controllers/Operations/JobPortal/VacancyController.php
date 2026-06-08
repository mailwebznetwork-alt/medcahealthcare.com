<?php

namespace App\Http\Controllers\Operations\JobPortal;

use App\Enums\EmploymentType;
use App\Enums\VacancyVisibility;
use App\Enums\VacancyWorkflowStatus;
use App\Http\Controllers\Concerns\InteractsWithLegacyManagedModules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\StoreVacancyRequest;
use App\Http\Requests\Operations\UpdateVacancyRequest;
use App\Models\Page;
use App\Models\Vacancy;
use App\Services\DynamicModules\LegacyManagedModuleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class VacancyController extends Controller
{
    use InteractsWithLegacyManagedModules;

    public function __construct()
    {
        $this->authorizeResource(Vacancy::class, 'vacancy');
    }

    public function index(): View
    {
        return view('operations.job-portal.vacancies.index');
    }

    public function create(): View
    {
        $vacancy = new Vacancy([
            'employment_type' => EmploymentType::FullTime,
            'visibility' => VacancyVisibility::Public,
            'workflow_status' => VacancyWorkflowStatus::Draft,
            'is_active' => true,
            'sort_order' => 0,
            'country_code' => 'IN',
            'salary_currency' => 'INR',
        ]);

        $detailPages = $this->detailPagesForForm();

        return view('operations.job-portal.vacancies.create', array_merge(
            compact('vacancy', 'detailPages'),
            $this->legacyModuleContext(LegacyManagedModuleRegistry::JOB_PORTAL),
        ));
    }

    public function store(StoreVacancyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['detail_page_id'] = $this->normalizeDetailPageId($data['detail_page_id'] ?? null);
        $data['slug'] = Vacancy::generateUniqueSlug(
            $data['title'],
            $data['city'] ?? null,
            $data['pin_code'] ?? null
        );

        $vacancy = new Vacancy($data);
        $this->syncPublishedTimestamp($vacancy);
        $vacancy->save();

        $this->persistLegacyCustomFields($request, LegacyManagedModuleRegistry::JOB_PORTAL, $vacancy);

        return redirect()
            ->route('operations.job-portal.vacancies.edit', $vacancy)
            ->with('status', 'vacancy-created');
    }

    public function show(Vacancy $vacancy): View
    {
        return view('operations.job-portal.vacancies.show', compact('vacancy'));
    }

    public function edit(Vacancy $vacancy): View
    {
        $detailPages = $this->detailPagesForForm();

        return view('operations.job-portal.vacancies.edit', array_merge(
            compact('vacancy', 'detailPages'),
            $this->legacyModuleContext(LegacyManagedModuleRegistry::JOB_PORTAL, $vacancy),
        ));
    }

    public function update(UpdateVacancyRequest $request, Vacancy $vacancy): RedirectResponse
    {
        $data = $request->validated();
        $data['detail_page_id'] = $this->normalizeDetailPageId($data['detail_page_id'] ?? null);
        $vacancy->fill($data);
        $this->syncPublishedTimestamp($vacancy);
        $vacancy->save();

        $this->persistLegacyCustomFields($request, LegacyManagedModuleRegistry::JOB_PORTAL, $vacancy);

        return redirect()
            ->route('operations.job-portal.vacancies.edit', $vacancy)
            ->with('status', 'vacancy-updated');
    }

    public function destroy(Vacancy $vacancy): RedirectResponse
    {
        $vacancy->delete();

        return redirect()
            ->route('operations.job-portal.vacancies.index')
            ->with('status', 'vacancy-deleted');
    }

    public function duplicate(Request $request, Vacancy $vacancy): RedirectResponse
    {
        $this->authorize('create', Vacancy::class);
        $this->authorize('view', $vacancy);
        $copy = $vacancy->duplicateAsDraft();

        return redirect()
            ->route('operations.job-portal.vacancies.edit', $copy)
            ->with('status', 'vacancy-duplicated');
    }

    private function syncPublishedTimestamp(Vacancy $vacancy): void
    {
        if ($vacancy->workflow_status === VacancyWorkflowStatus::Published) {
            if ($vacancy->published_at === null) {
                $vacancy->published_at = now();
            }

            return;
        }

        if ($vacancy->workflow_status === VacancyWorkflowStatus::Draft) {
            $vacancy->published_at = null;
        }
    }

    /**
     * @return Collection<int, Page>
     */
    private function detailPagesForForm(): Collection
    {
        return Page::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);
    }

    private function normalizeDetailPageId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === '0' || $value === 0) {
            return null;
        }

        return (int) $value;
    }
}
