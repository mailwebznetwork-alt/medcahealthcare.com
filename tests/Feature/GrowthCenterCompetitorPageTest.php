<?php

use App\Models\Competitor;
use App\Models\CompetitorKeyword;
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
