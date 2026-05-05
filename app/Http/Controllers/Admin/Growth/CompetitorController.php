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

class CompetitorController extends Controller
{
    public function __construct(
        private readonly CompetitorComparisonService $comparisonService
    ) {}

    public function index(): JsonResponse
    {
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
        $validated = $request->validated();

        $competitors = collect($validated['competitors'])->map(function (array $data) {
            return [
                'name' => $data['name'],
                'website' => $data['website'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_intercept_target' => $data['is_intercept_target'] ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        Competitor::insert($competitors);

        return response()->json([
            'message' => 'Competitors successfully added.',
            'count' => count($competitors),
        ], 201);
    }

    public function compare(CompareCompetitorsRequest $request): JsonResponse
    {
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
