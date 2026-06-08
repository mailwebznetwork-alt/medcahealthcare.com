<?php

namespace App\Http\Controllers\Operations\ServiceCategories;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\ServiceCategories\StoreServiceCategoryRequest;
use App\Http\Requests\Operations\ServiceCategories\UpdateServiceCategoryRequest;
use App\Models\ServiceCategory;
use App\Repositories\Operations\ServiceCategoryRepository;
use App\Services\Operations\ServiceCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceCategoryController extends Controller
{
    public function __construct(
        private readonly ServiceCategoryRepository $repository,
        private readonly ServiceCategoryService $categoryService,
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

    public function edit(ServiceCategory $service_category): View
    {
        $this->authorize('update', $service_category);

        return view('operations.service-categories.edit', [
            'category' => $service_category,
            'parentOptions' => $this->repository->parentOptions($service_category->id),
        ]);
    }

    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $service_category): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $this->categoryService->update($service_category, $data);

        return redirect()
            ->route('operations.service-categories.index')
            ->with('status', 'service-category-updated');
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
