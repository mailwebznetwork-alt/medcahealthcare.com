<?php

namespace App\Http\Controllers\Growth;

use App\Http\Controllers\Controller;
use App\Models\Competitor;
use App\Models\CompetitorKeyword;
use App\Models\CompetitorLead;
use App\Models\CompetitorTracking;
use App\Services\CompetitorComparisonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompetitorPageController extends Controller
{
    public function __construct(private readonly CompetitorComparisonService $comparisonService) {}

    public function __invoke(Request $request): View
    {
        $competitors = Competitor::query()
            ->withCount(['keywords', 'leads'])
            ->orderByDesc('is_intercept_target')
            ->orderBy('name')
            ->paginate(20);

        $summary = [
            'total_competitors' => Competitor::query()->count(),
            'active_competitors' => Competitor::query()->active()->count(),
            'best_competitor' => $this->comparisonService->getBestPerformer(),
            'worst_competitor' => $this->comparisonService->getWorstPerformer(),
        ];

        $comparison = null;
        $overlap = null;
        $selectedIds = [];
        $queryIds = $request->query('compare_ids');
        if (is_string($queryIds) && $queryIds !== '') {
            $selectedIds = collect(explode(',', $queryIds))
                ->map(fn (string $id): int => (int) trim($id))
                ->filter(fn (int $id): bool => $id > 0)
                ->values()
                ->all();

            if (count($selectedIds) >= 2) {
                $comparison = $this->comparisonService->compareCompetitors($selectedIds);
                $overlap = $this->comparisonService->getKeywordOverlap($selectedIds);
            }
        }

        return view('growth-center.competitors.index', [
            'competitors' => $competitors,
            'allCompetitors' => Competitor::query()->orderBy('name')->get(['id', 'name']),
            'allKeywords' => CompetitorKeyword::query()
                ->with('competitor:id,name')
                ->orderByDesc('id')
                ->limit(300)
                ->get(['id', 'competitor_id', 'keyword']),
            'summary' => $summary,
            'comparison' => $comparison,
            'keywordOverlap' => $overlap,
            'selectedCompetitorIds' => $selectedIds,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'is_intercept_target' => ['nullable', 'boolean'],
        ]);

        Competitor::query()->updateOrCreate(
            ['name' => Str::of($validated['name'])->trim()->toString()],
            [
                'website' => isset($validated['website']) ? Str::of((string) $validated['website'])->trim()->toString() : null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'is_intercept_target' => (bool) ($validated['is_intercept_target'] ?? false),
            ]
        );

        return redirect()
            ->route('growth-center.competitors.index')
            ->with('status', __('Competitor saved successfully.'));
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bulk_competitors' => ['required', 'string', 'max:20000'],
        ]);

        $rows = preg_split('/\r\n|\r|\n/', $validated['bulk_competitors']) ?: [];
        $count = 0;

        foreach ($rows as $row) {
            $line = trim($row);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $name = $parts[0] ?? '';
            if ($name === '') {
                continue;
            }

            $website = $parts[1] ?? null;
            $isIntercept = isset($parts[2]) ? in_array(strtolower($parts[2]), ['1', 'yes', 'true', 'y'], true) : false;

            Competitor::query()->updateOrCreate(
                ['name' => $name],
                [
                    'website' => $website !== '' ? $website : null,
                    'is_active' => true,
                    'is_intercept_target' => $isIntercept,
                ]
            );
            $count++;
        }

        return redirect()
            ->route('growth-center.competitors.index')
            ->with('status', __('Bulk import completed. :count competitor(s) processed.', ['count' => $count]));
    }

    public function compare(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'competitor_ids' => ['required', 'array', 'min:2', 'max:10'],
            'competitor_ids.*' => ['required', 'integer', 'exists:competitors,id'],
        ]);

        return redirect()->route('growth-center.competitors.index', [
            'compare_ids' => implode(',', $validated['competitor_ids']),
        ]);
    }

    public function destroy(Competitor $competitor): RedirectResponse
    {
        $competitor->delete();

        return redirect()
            ->route('growth-center.competitors.index')
            ->with('status', __('Competitor removed successfully.'));
    }

    public function storeKeyword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'competitor_id' => ['required', 'integer', 'exists:competitors,id'],
            'keyword' => ['required', 'string', 'max:255'],
            'intent_type' => ['required', 'in:brand,service,local'],
            'search_volume' => ['nullable', 'integer', 'min:0'],
            'difficulty' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        CompetitorKeyword::query()->updateOrCreate(
            [
                'competitor_id' => (int) $validated['competitor_id'],
                'keyword' => Str::of($validated['keyword'])->trim()->toString(),
            ],
            [
                'intent_type' => $validated['intent_type'],
                'search_volume' => $validated['search_volume'] ?? null,
                'difficulty' => $validated['difficulty'] ?? null,
            ]
        );

        return redirect()
            ->route('growth-center.competitors.index')
            ->with('status', __('Keyword saved successfully.'));
    }

    public function storeTracking(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'competitor_keyword_id' => ['required', 'integer', 'exists:competitor_keywords,id'],
            'clicks' => ['required', 'integer', 'min:0'],
            'impressions' => ['required', 'integer', 'min:0'],
            'position' => ['nullable', 'integer', 'min:1'],
            'recorded_date' => ['required', 'date'],
        ]);

        CompetitorTracking::query()->create([
            'competitor_keyword_id' => (int) $validated['competitor_keyword_id'],
            'clicks' => (int) $validated['clicks'],
            'impressions' => (int) $validated['impressions'],
            'position' => $validated['position'] ?? null,
            'recorded_date' => $validated['recorded_date'],
        ]);

        return redirect()
            ->route('growth-center.competitors.index')
            ->with('status', __('Tracking data added successfully.'));
    }

    public function storeLead(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'competitor_keyword_id' => ['nullable', 'integer', 'exists:competitor_keywords,id'],
            'source' => ['required', 'in:google_ads,seo,meta,direct'],
            'status' => ['required', 'in:new,contacted,converted,lost'],
            'details' => ['nullable', 'string', 'max:5000'],
        ]);

        CompetitorLead::query()->create([
            'competitor_keyword_id' => $validated['competitor_keyword_id'] ?? null,
            'source' => $validated['source'],
            'status' => $validated['status'],
            'details' => isset($validated['details']) && trim($validated['details']) !== ''
                ? json_encode(['note' => trim($validated['details'])], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
        ]);

        return redirect()
            ->route('growth-center.competitors.index')
            ->with('status', __('Lead attribution added successfully.'));
    }
}
