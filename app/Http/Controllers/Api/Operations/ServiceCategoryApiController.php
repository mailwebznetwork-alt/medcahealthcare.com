<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceCategoryResource;
use App\Http\Resources\ServiceSummaryResource;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Repositories\Operations\ServiceCategoryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceCategoryApiController extends Controller
{
    public function __construct(
        private readonly ServiceCategoryRepository $repository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ServiceCategory::class);

        $categories = ServiceCategory::query()
            ->when($request->boolean('active_only', true), fn ($q) => $q->active())
            ->ordered()
            ->withCount('services')
            ->get();

        return ServiceCategoryResource::collection($categories);
    }

    public function show(ServiceCategory $serviceCategory): ServiceCategoryResource
    {
        $this->authorize('view', $serviceCategory);

        $serviceCategory->loadCount('services');

        return new ServiceCategoryResource($serviceCategory);
    }

    public function services(Request $request, ServiceCategory $serviceCategory): JsonResponse
    {
        $this->authorize('view', $serviceCategory);

        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $services = Service::query()
            ->whereHas('categories', fn ($q) => $q->whereKey($serviceCategory->id))
            ->with('categories')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate($perPage);

        return response()->json([
            'category' => new ServiceCategoryResource($serviceCategory),
            'services' => ServiceSummaryResource::collection($services),
            'meta' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
            ],
        ]);
    }

    public function picker(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ServiceCategory::class);

        return ServiceCategoryResource::collection($this->repository->activeForPicker());
    }
}
