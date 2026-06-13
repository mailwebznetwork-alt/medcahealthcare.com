<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 50), 100);

        $query = Service::query()
            ->with(['seo', 'categories'])
            ->where('is_active', true);

        if ($request->filled('category_code')) {
            $code = $request->string('category_code')->toString();
            $query->whereHas('categories', fn ($q) => $q->where('code', $code));
        }

        $paginator = $query->orderBy('service_code')->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Service $s) => $this->transform($s))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(string $code): JsonResponse
    {
        $service = Service::query()
            ->with(['seo', 'categories', 'subServices'])
            ->where('service_code', $code)
            ->where('is_active', true)
            ->first();

        if ($service === null) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $data = $this->transform($service);
        $data['sub_services'] = $service->subServices->map(fn ($sub) => [
            'code' => $sub->sub_service_code,
            'title' => $sub->title,
            'url' => $sub->publicUrl(),
        ])->values();

        return response()->json(['data' => $data]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Service $service): array
    {
        return [
            'code' => $service->service_code,
            'title' => $service->publicListingTitle(),
            'url' => $service->publicUrl(),
            'short_summary' => $service->short_summary,
            'description' => $service->description,
            'quick_answer' => $service->quick_answer,
            'ai_summary' => $service->ai_summary,
            'why_medca' => $service->why_medca,
            'categories' => $service->categories->pluck('code')->values(),
            'meta_title' => $service->seo?->meta_title,
            'meta_description' => $service->seo?->meta_description,
            'search_intent' => $service->seo?->search_intent,
            'medical_review_status' => $service->medical_review_status?->value ?? $service->medical_review_status,
            'verification_status' => $service->verification_status?->value ?? $service->verification_status,
            'publish_status' => $service->publish_status?->value ?? $service->publish_status,
        ];
    }
}
