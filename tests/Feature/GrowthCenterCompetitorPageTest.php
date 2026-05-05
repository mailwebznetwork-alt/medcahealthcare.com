<?php

use App\Models\Competitor;
use App\Models\CompetitorKeyword;
use App\Models\CompetitorLead;
use App\Models\CompetitorTracking;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

it('shows growth center competitors page for authorized users', function () {
    if (! Schema::hasTable('competitors')) {
        $this->markTestSkipped('Competitors table is not migrated.');
    }

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => ['growth_center' => true],
    ]);

    Competitor::query()->create([
        'name' => 'Demo Competitor',
        'website' => 'https://demo.example.com',
        'is_active' => true,
        'is_intercept_target' => true,
    ]);

    $this->actingAs($user)
        ->get(route('growth-center.competitors.index'))
        ->assertOk()
        ->assertSeeText('Competitors')
        ->assertSeeText('Demo Competitor');
});

it('stores competitor from growth center form', function () {
    if (! Schema::hasTable('competitors')) {
        $this->markTestSkipped('Competitors table is not migrated.');
    }

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => ['growth_center' => true],
    ]);

    $this->actingAs($user)
        ->post(route('growth-center.competitors.store'), [
            'name' => 'Form Competitor',
            'website' => 'https://form.example.com',
            'is_intercept_target' => 1,
        ])
        ->assertRedirect(route('growth-center.competitors.index'));

    $this->assertDatabaseHas('competitors', [
        'name' => 'Form Competitor',
        'is_intercept_target' => 1,
    ]);
});

it('bulk stores and compares competitors from growth center', function () {
    if (! Schema::hasTable('competitors') || ! Schema::hasTable('competitor_keywords')) {
        $this->markTestSkipped('Competitor module tables are not migrated.');
    }

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => ['growth_center' => true],
    ]);

    $this->actingAs($user)
        ->post(route('growth-center.competitors.bulk-store'), [
            'bulk_competitors' => "Bulk A|https://a.example|yes\nBulk B|https://b.example|no",
        ])
        ->assertRedirect(route('growth-center.competitors.index'));

    $a = Competitor::query()->where('name', 'Bulk A')->firstOrFail();
    $b = Competitor::query()->where('name', 'Bulk B')->firstOrFail();

    CompetitorKeyword::query()->create([
        'competitor_id' => $a->id,
        'keyword' => 'diagnostic center',
        'intent_type' => 'service',
    ]);
    CompetitorKeyword::query()->create([
        'competitor_id' => $b->id,
        'keyword' => 'diagnostic center',
        'intent_type' => 'service',
    ]);

    $this->actingAs($user)
        ->get(route('growth-center.competitors.index', ['compare_ids' => $a->id.','.$b->id]))
        ->assertOk()
        ->assertSeeText('Comparison Result')
        ->assertSeeText('Keyword Overlap');
});

it('removes competitor from growth center list', function () {
    if (! Schema::hasTable('competitors')) {
        $this->markTestSkipped('Competitors table is not migrated.');
    }

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => ['growth_center' => true],
    ]);

    $competitor = Competitor::query()->create([
        'name' => 'Delete Me Competitor',
        'website' => 'https://deleteme.example.com',
        'is_active' => true,
        'is_intercept_target' => false,
    ]);

    $this->actingAs($user)
        ->delete(route('growth-center.competitors.destroy', $competitor))
        ->assertRedirect(route('growth-center.competitors.index'));

    $this->assertDatabaseMissing('competitors', [
        'id' => $competitor->id,
    ]);
});

it('stores keyword tracking and lead attribution from forms', function () {
    if (! Schema::hasTable('competitors') || ! Schema::hasTable('competitor_keywords')) {
        $this->markTestSkipped('Competitor module tables are not migrated.');
    }

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => ['growth_center' => true],
    ]);

    $competitor = Competitor::query()->create([
        'name' => 'Signals Competitor',
        'website' => 'https://signals.example.com',
        'is_active' => true,
        'is_intercept_target' => true,
    ]);

    $this->actingAs($user)
        ->post(route('growth-center.competitors.keywords.store'), [
            'competitor_id' => $competitor->id,
            'keyword' => 'diagnostic near arekere',
            'intent_type' => 'local',
            'search_volume' => 1200,
            'difficulty' => 37,
        ])
        ->assertRedirect(route('growth-center.competitors.index'));

    $keyword = CompetitorKeyword::query()
        ->where('competitor_id', $competitor->id)
        ->where('keyword', 'diagnostic near arekere')
        ->firstOrFail();

    $this->actingAs($user)
        ->post(route('growth-center.competitors.tracking.store'), [
            'competitor_keyword_id' => $keyword->id,
            'clicks' => 45,
            'impressions' => 950,
            'position' => 3,
            'recorded_date' => now()->toDateString(),
        ])
        ->assertRedirect(route('growth-center.competitors.index'));

    $this->actingAs($user)
        ->post(route('growth-center.competitors.leads.store'), [
            'competitor_keyword_id' => $keyword->id,
            'source' => 'google_ads',
            'status' => 'converted',
            'details' => 'phone lead from local campaign',
        ])
        ->assertRedirect(route('growth-center.competitors.index'));

    expect(CompetitorTracking::query()->where('competitor_keyword_id', $keyword->id)->exists())->toBeTrue();
    expect(CompetitorLead::query()->where('competitor_keyword_id', $keyword->id)->where('status', 'converted')->exists())->toBeTrue();
});
