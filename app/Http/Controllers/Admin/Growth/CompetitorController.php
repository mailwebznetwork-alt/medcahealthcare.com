<?php

namespace App\Http\Controllers\Admin\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\BulkStoreCompetitorRequest;
use App\Http\Requests\Growth\CompareCompetitorsRequest;
use App\Models\Competitor;
use App\Models\CompetitorKeyword;
use App\Models\CompetitorLead;
use App\Services\CompetitorComparisonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CompetitorController extends Controller
{
    public function __construct(
        private readonly CompetitorComparisonService $comparisonService
    ) {}

    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Competitor::class);

        $competitors = Competitor::withCount(['keywords', 'leads'])
            ->orderByDesc('is_intercept_target')
            ->orderBy('name')
            ->paginate(20)
            ->through(fn (Competitor $competitor) => [
                'id' => $competitor->id,
                'name' => $competitor->name,
                'website' => $competitor->website,
                'is_active' => $competitor->is_active,
                'is_intercept_target' => $competitor->is_intercept_target,
                'total_keywords' => $competitor->keywordsCount(),
                'total_conversions' => $competitor->totalConversions(),
                'created_at' => $competitor->created_at,
            ]);

        return response()->json($competitors);
    }

    public function bulkStore(BulkStoreCompetitorRequest $request): JsonResponse
    {
        Gate::authorize('create', Competitor::class);

        $validated = $request->validated();
        $count = 0;

        foreach ($validated['competitors'] as $data) {
            Competitor::query()->updateOrCreate(
                ['name' => $data['name']],
                [
                    'website' => $data['website'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                    'is_intercept_target' => $data['is_intercept_target'] ?? false,
                ]
            );
            $count++;
        }

        return response()->json([
            'message' => 'Competitors successfully added.',
            'count' => $count,
        ], 201);
    }

    public function compare(CompareCompetitorsRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Competitor::class);

        $competitorIds = $request->validated('competitor_ids');

        $comparison = $this->comparisonService->compareCompetitors($competitorIds);
        $overlap = $this->comparisonService->getKeywordOverlap($competitorIds);

        return response()->json([
            'comparison' => $comparison,
            'keyword_overlap' => $overlap,
        ]);
    }

    public function summary(): JsonResponse
    {
        Gate::authorize('viewAny', Competitor::class);

        $totalCompetitors = Competitor::count();
        $activeCompetitors = Competitor::active()->count();
        $totalKeywords = CompetitorKeyword::count();
        $totalConversions = CompetitorLead::count();

        return response()->json([
            'total_competitors' => $totalCompetitors,
            'active_competitors' => $activeCompetitors,
            'total_keywords' => $totalKeywords,
            'total_conversions' => $totalConversions,
            'best_competitor' => $this->comparisonService->getBestPerformer(),
            'worst_competitor' => $this->comparisonService->getWorstPerformer(),
        ]);
    }
}
