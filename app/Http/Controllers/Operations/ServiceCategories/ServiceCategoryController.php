<?php

namespace App\Http\Controllers\Operations\ServiceCategories;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\ServiceCategories\StoreServiceCategoryRequest;
use App\Http\Requests\Operations\ServiceCategories\UpdateServiceCategoryRequest;
use App\Models\ServiceCategory;
use App\Repositories\Operations\ServiceCategoryRepository;
use App\Services\Operations\BackgroundCategoryOrchestratorDispatcher;
use App\Services\Operations\BackgroundMatrixReconcileDispatcher;
use App\Services\Operations\CatalogFormViewData;
use App\Services\Operations\CatalogMasterPersister;
use App\Services\Operations\ServiceCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceCategoryController extends Controller
{
    public function __construct(
        private readonly ServiceCategoryRepository $repository,
        private readonly ServiceCategoryService $categoryService,
        private readonly CatalogMasterPersister $catalogPersister,
        private readonly CatalogFormViewData $catalogFormViewData,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', ServiceCategory::class);

        return view('operations.service-categories.index');
    }

    public function create(): View
    {
        $this->authorize('create', ServiceCategory::class);

        $category = new ServiceCategory([
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return view('operations.service-categories.create', [
            'category' => $category,
            'parentOptions' => $this->repository->parentOptions(),
        ]);
    }

    public function store(StoreServiceCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $this->categoryService->create($data);

        return redirect()
            ->route('operations.service-categories.index')
            ->with('status', 'service-category-created');
    }

    public function edit(Request $request, ServiceCategory $service_category): View
    {
        $this->authorize('update', $service_category);

        return view('operations.service-categories.edit', array_merge(
            ['category' => $service_category],
            $this->catalogFormViewData->forCategory($request, $service_category),
        ));
    }

    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $service_category): RedirectResponse
    {
        $data = $request->validated();

        $persistResult = DB::transaction(function () use ($request, $data, $service_category) {
            return $this->catalogPersister->persistCategory($service_category, $request, $data);
        });

        if ($persistResult->reconcileServiceIds !== []) {
            app(BackgroundMatrixReconcileDispatcher::class)->dispatchMany($persistResult->reconcileServiceIds);
        }

        if ($persistResult->runCategoryOrchestrator) {
            app(BackgroundCategoryOrchestratorDispatcher::class)->dispatch((int) $persistResult->category->id);
        }

        $tab = (string) $request->input('active_tab', $request->query('tab', 'basic'));
        $params = ['service_category' => $service_category->fresh()];
        if ($tab !== '' && $tab !== 'basic') {
            $params['tab'] = $tab;
        }

        return redirect()
            ->route('operations.service-categories.edit', $params)
            ->with('status', __('Category updated.'));
    }

    public function destroy(ServiceCategory $service_category): RedirectResponse
    {
        $this->authorize('delete', $service_category);

        $this->categoryService->delete($service_category);

        return redirect()
            ->route('operations.service-categories.index')
            ->with('status', 'service-category-deleted');
    }
}
