<?php

use App\Models\Competitor;
use App\Models\CompetitorKeyword;
use App\Models\CompetitorLead;
use App\Models\CompetitorTracking;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

it('stores competitors in bulk and returns paginated list', function () {
    if (! Schema::hasTable('competitors')) {
        $this->markTestSkipped('Competitors table is not migrated.');
    }

    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $this->postJson('/api/admin/growth/competitors/bulk', [
        'competitors' => [
            [
                'name' => 'Alpha Health',
                'website' => 'https://alpha.example.com',
                'is_active' => true,
                'is_intercept_target' => true,
            ],
            [
                'name' => 'Beta Care',
                'website' => 'https://beta.example.com',
            ],
        ],
    ])->assertCreated()->assertJsonPath('message', 'Competitors successfully added.');

    $this->getJson('/api/admin/growth/competitors')
        ->assertOk()
        ->assertJsonStructure(['data', 'total', 'current_page']);
});

it('returns compare and summary payloads', function () {
    if (! Schema::hasTable('competitors') || ! Schema::hasTable('competitor_keywords')) {
        $this->markTestSkipped('Competitor module tables are not migrated.');
    }

    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $a = Competitor::query()->create(['name' => 'Comp A', 'is_intercept_target' => true]);
    $b = Competitor::query()->create(['name' => 'Comp B']);

    $aKeyword = CompetitorKeyword::query()->create([
        'competitor_id' => $a->id,
        'keyword' => 'arekere diagnostics',
        'intent_type' => 'local',
    ]);
    $bKeyword = CompetitorKeyword::query()->create([
        'competitor_id' => $b->id,
        'keyword' => 'arekere diagnostics',
        'intent_type' => 'local',
    ]);

    CompetitorTracking::query()->create([
        'competitor_keyword_id' => $aKeyword->id,
        'clicks' => 100,
        'impressions' => 1000,
        'position' => 2,
        'recorded_date' => now()->toDateString(),
    ]);
    CompetitorTracking::query()->create([
        'competitor_keyword_id' => $bKeyword->id,
        'clicks' => 20,
        'impressions' => 300,
        'position' => 5,
        'recorded_date' => now()->toDateString(),
    ]);

    CompetitorLead::query()->create([
        'competitor_keyword_id' => $aKeyword->id,
        'source' => 'seo',
        'status' => 'converted',
    ]);
    CompetitorLead::query()->create([
        'competitor_keyword_id' => $bKeyword->id,
        'source' => 'google_ads',
        'status' => 'new',
    ]);

    $this->postJson('/api/admin/growth/competitors/compare', [
        'competitor_ids' => [$a->id, $b->id],
    ])->assertOk()
        ->assertJsonStructure(['comparison', 'keyword_overlap']);

    $this->getJson('/api/admin/growth/competitors/summary')
        ->assertOk()
        ->assertJsonStructure(['best_competitor', 'worst_competitor']);
});
