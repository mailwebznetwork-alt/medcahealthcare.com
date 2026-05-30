<?php

use App\Jobs\AnalyzeHijackOpportunityJob;
use App\Models\Competitor;
use App\Models\CompetitorKeyword;
use App\Models\CompetitorTracking;
use App\Models\SiteKeywordRanking;
use App\Models\User;
use App\Services\Growth\CompetitorComparisonService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

it('identifies hijack opportunities when competitor outranks medca on high intent keywords', function () {
    if (! Schema::hasTable('competitor_keywords') || ! Schema::hasColumn('competitor_keywords', 'hijack_priority')) {
        $this->markTestSkipped('Hijack columns are not migrated.');
    }

    $competitor = Competitor::query()->create(['name' => 'Rival Labs', 'is_active' => true]);
    $keyword = CompetitorKeyword::query()->create([
        'competitor_id' => $competitor->id,
        'keyword' => 'arekere blood test home',
        'intent_type' => 'local',
        'search_volume' => 1500,
        'difficulty' => 35,
    ]);

    SiteKeywordRanking::query()->create([
        'keyword' => 'arekere blood test home',
        'position' => 8,
        'recorded_date' => now()->toDateString(),
    ]);

    CompetitorTracking::query()->create([
        'competitor_keyword_id' => $keyword->id,
        'clicks' => 10,
        'impressions' => 100,
        'position' => 3,
        'recorded_date' => now()->toDateString(),
    ]);

    $opportunities = app(CompetitorComparisonService::class)->identifyHighValueOpportunities();

    expect($opportunities)->toHaveCount(1)
        ->and($opportunities->first()['hijack_priority'])->toBeGreaterThanOrEqual(1)
        ->and($keyword->fresh()->hijack_priority)->not->toBeNull();
});

it('dispatches analyze job when competitor ranking improves', function () {
    if (! Schema::hasTable('competitor_keywords') || ! Schema::hasColumn('competitor_keywords', 'hijack_priority')) {
        $this->markTestSkipped('Hijack columns are not migrated.');
    }

    Queue::fake();

    $competitor = Competitor::query()->create(['name' => 'Alpha Care', 'is_active' => true]);
    $keyword = CompetitorKeyword::query()->create([
        'competitor_id' => $competitor->id,
        'keyword' => 'diagnostic center hulimavu',
        'intent_type' => 'service',
    ]);

    SiteKeywordRanking::query()->create([
        'keyword' => 'diagnostic center hulimavu',
        'position' => 12,
        'recorded_date' => now()->subDay()->toDateString(),
    ]);

    CompetitorTracking::query()->create([
        'competitor_keyword_id' => $keyword->id,
        'clicks' => 5,
        'impressions' => 50,
        'position' => 9,
        'recorded_date' => now()->subDay()->toDateString(),
    ]);

    CompetitorTracking::query()->create([
        'competitor_keyword_id' => $keyword->id,
        'clicks' => 8,
        'impressions' => 80,
        'position' => 4,
        'recorded_date' => now()->toDateString(),
    ]);

    Queue::assertPushed(AnalyzeHijackOpportunityJob::class, fn (AnalyzeHijackOpportunityJob $job) => $job->competitorKeywordId === $keyword->id);
});

it('shows hijack opportunities tab in growth center', function () {
    if (! Schema::hasColumn('competitor_keywords', 'hijack_priority')) {
        $this->markTestSkipped('Hijack columns are not migrated.');
    }

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => ['growth_center' => true],
    ]);

    $this->actingAs($user)
        ->get(route('growth-center.competitors.index', ['tab' => 'hijack-opportunities']))
        ->assertOk()
        ->assertSeeText('Hijack Opportunities');
});
