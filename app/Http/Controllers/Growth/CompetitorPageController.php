<?php

namespace App\Http\Controllers\Growth;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use App\Models\Competitor;
use App\Models\CompetitorKeyword;
use App\Models\CompetitorLead;
use App\Models\CompetitorTracking;
use App\Models\GeoLocation;
use App\Models\PinCode;
use App\Models\SeoAiSignal;
use App\Models\SeoTechnical;
use App\Models\SiteKeywordRanking;
use App\Services\Growth\CompetitorComparisonService;
use App\Services\Growth\GeoService;
use App\Services\Growth\SeoEntityResolver;
use App\Services\Growth\WarRoomService;
use App\Support\GrowthReadinessReport;
use App\Support\WarRoomRollup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CompetitorPageController extends Controller
{
    public function __construct(
        private readonly CompetitorComparisonService $comparisonService,
        private readonly WarRoomService $warRoomService,
        private readonly GeoService $geoService
    ) {}

    public function __invoke(Request $request): View|SymfonyResponse
    {
        if (in_array($request->query('tab'), ['geo', 'aeo'], true)) {
            return redirect()->route('growth-center.competitors.index', array_merge(
                $request->except('tab'),
                ['tab' => 'seo']
            ));
        }

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

        $activeTab = (string) $request->query('tab', 'competitors');
        $allowedTabs = ['readiness', 'war-room', 'hijack-opportunities', 'seo', 'ga4', 'ai-pulse', 'competitors'];
        if (! in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'competitors';
        }

        $seoEntity = null;
        $seoTechnical = null;
        $seoAiSignal = null;
        $geoLocation = null;
        $pincodes = collect();
        $warRoomDashboard = [
            'pending_intercepts' => 0,
            'in_progress_intercepts' => 0,
            'completed_intercepts' => 0,
            'high_priority_count' => 0,
            'intercepts' => collect(),
        ];

        if (Schema::hasTable('seo_entities')) {
            $seoEntity = app(SeoEntityResolver::class)->forCurrentBusiness();
        }
        if (Schema::hasTable('seo_technical')) {
            $seoTechnical = SeoTechnical::query()->latest('id')->first();
        }
        if (Schema::hasTable('seo_ai_signals')) {
            $seoAiSignal = SeoAiSignal::query()->latest('id')->first();
        }
        if (Schema::hasTable('geo_locations')) {
            $geoLocation = GeoLocation::query()->latest('id')->first();
        }
        if (Schema::hasTable('pin_codes')) {
            $pincodes = PinCode::query()->latest('id')->limit(100)->get();
        }
        if (Schema::hasTable('intercepts')) {
            $warRoomDashboard = $this->warRoomService->getDashboard();
        }

        $hijackOpportunities = collect();
        if (Schema::hasTable('competitor_keywords') && Schema::hasColumn('competitor_keywords', 'hijack_priority')) {
            $hijackOpportunities = $this->comparisonService->listHighValueOpportunities();
        }

        $hijackStrategies = $seoEntity?->hijackStrategies() ?? [];

        $businessProfile = null;
        if (Schema::hasTable('business_profiles')) {
            $businessProfile = BusinessProfile::query()->where('website', config('app.url'))->first()
                ?? BusinessProfile::query()->latest('id')->first();
        }

        return view('growth-center.competitors.index', [
            'competitors' => $competitors,
            'growthReadinessReport' => GrowthReadinessReport::cached(),
            'warRoomRollup' => WarRoomRollup::cached(),
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
            'activeTab' => $activeTab,
            'businessProfile' => $businessProfile,
            'seoEntity' => $seoEntity,
            'seoTechnical' => $seoTechnical,
            'seoAiSignal' => $seoAiSignal,
            'geoLocation' => $geoLocation,
            'pincodes' => $pincodes,
            'geoStats' => $this->geoService->getCoverageStats(),
            'warRoomDashboard' => $warRoomDashboard,
            'intercepts' => $warRoomDashboard['intercepts'] ?? collect(),
            'hijackOpportunities' => $hijackOpportunities,
            'hijackStrategies' => $hijackStrategies,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Competitor::class);
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

        WarRoomRollup::forget();

        return redirect()
            ->route('growth-center.competitors.index')
            ->with('status', __('Competitor saved successfully.'));
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        Gate::authorize('create', Competitor::class);
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

        WarRoomRollup::forget();

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
        Gate::authorize('delete', $competitor);
        $competitor->delete();

        WarRoomRollup::forget();

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

        WarRoomRollup::forget();

        return redirect()
            ->route('growth-center.competitors.index')
            ->with('status', __('Keyword saved successfully.'));
    }

    public function storeKeywordsBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'competitor_id' => ['required', 'integer', 'exists:competitors,id'],
            'bulk_keywords' => ['required', 'string', 'max:20000'],
        ]);

        $rows = preg_split('/\r\n|\r|\n/', $validated['bulk_keywords']) ?: [];
        $processed = 0;

        foreach ($rows as $row) {
            $line = trim($row);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $keyword = $parts[0] ?? '';
            if ($keyword === '') {
                continue;
            }

            $intentType = strtolower($parts[1] ?? 'service');
            if (! in_array($intentType, ['brand', 'service', 'local'], true)) {
                $intentType = 'service';
            }

            $searchVolume = isset($parts[2]) && is_numeric($parts[2]) ? (int) $parts[2] : null;
            $difficulty = isset($parts[3]) && is_numeric($parts[3]) ? (int) $parts[3] : null;
            if ($difficulty !== null) {
                $difficulty = min(100, max(0, $difficulty));
            }

            CompetitorKeyword::query()->updateOrCreate(
                [
                    'competitor_id' => (int) $validated['competitor_id'],
                    'keyword' => $keyword,
                ],
                [
                    'intent_type' => $intentType,
                    'search_volume' => $searchVolume,
                    'difficulty' => $difficulty,
                ]
            );
            $processed++;
        }

        WarRoomRollup::forget();

        return redirect()
            ->route('growth-center.competitors.index')
            ->with('status', __('Bulk keywords saved. :count keyword(s) processed.', ['count' => $processed]));
    }

    public function storeTracking(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'competitor_keyword_id' => ['required', 'integer', 'exists:competitor_keywords,id'],
            'clicks' => ['required', 'integer', 'min:0'],
            'impressions' => ['required', 'integer', 'min:0'],
            'position' => ['nullable', 'integer', 'min:1'],
            'our_position' => ['nullable', 'integer', 'min:1'],
            'recorded_date' => ['required', 'date'],
        ]);

        $keyword = CompetitorKeyword::query()->findOrFail((int) $validated['competitor_keyword_id']);

        CompetitorTracking::query()->create([
            'competitor_keyword_id' => (int) $validated['competitor_keyword_id'],
            'clicks' => (int) $validated['clicks'],
            'impressions' => (int) $validated['impressions'],
            'position' => $validated['position'] ?? null,
            'recorded_date' => $validated['recorded_date'],
        ]);

        if (isset($validated['our_position']) && Schema::hasTable('site_keyword_rankings')) {
            SiteKeywordRanking::query()->create([
                'keyword' => SiteKeywordRanking::normalizeKeyword($keyword->keyword),
                'position' => (int) $validated['our_position'],
                'recorded_date' => $validated['recorded_date'],
            ]);
        }

        WarRoomRollup::forget();

        return redirect()
            ->route('growth-center.competitors.index')
            ->with('status', __('Tracking data added successfully.'));
    }

    public function storeOurRanking(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:1'],
            'recorded_date' => ['required', 'date'],
        ]);

        if (! Schema::hasTable('site_keyword_rankings')) {
            return redirect()
                ->route('growth-center.competitors.index', ['tab' => 'hijack-opportunities'])
                ->withErrors(['keyword' => __('Site keyword rankings are not available yet. Run migrations.')]);
        }

        SiteKeywordRanking::query()->create([
            'keyword' => SiteKeywordRanking::normalizeKeyword($validated['keyword']),
            'position' => (int) $validated['position'],
            'recorded_date' => $validated['recorded_date'],
        ]);

        return redirect()
            ->route('growth-center.competitors.index', ['tab' => 'hijack-opportunities'])
            ->with('status', __('Medca ranking recorded. Hijack scan updated.'));
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

        WarRoomRollup::forget();

        return redirect()
            ->route('growth-center.competitors.index')
            ->with('status', __('Lead attribution added successfully.'));
    }
}
