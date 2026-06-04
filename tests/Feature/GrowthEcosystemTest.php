<?php

use App\Jobs\AutonomousContentJob;
use App\Livewire\SiteArchitect\Pages;
use App\Models\Competitor;
use App\Models\CompetitorBacklink;
use App\Models\CompetitorKeyword;
use App\Models\Page;
use App\Models\SeoEntity;
use App\Models\SiteBacklink;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Growth\BacklinkMonitorService;
use App\Services\Growth\HijackContentBridgeService;
use App\Support\GrowthReadinessReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

it('dispatches autonomous content job after hijack strategy persistence path', function () {
    Queue::fake();

    AutonomousContentJob::dispatch(42);

    Queue::assertPushed(AutonomousContentJob::class, fn (AutonomousContentJob $job) => $job->competitorKeywordId === 42);
});

it('merges autonomous gemini content into hijack strategy json', function () {
    if (! Schema::hasColumn('seo_entities', 'hijack_strategy')) {
        $this->markTestSkipped('Hijack strategy column missing.');
    }

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => '{"meta_title":"Arekere Home Nursing | Medca","meta_description":"Book trusted home nursing in Arekere with Medca Health Care. Same-day visits across Bengaluru.","h1":"Home Nursing in Arekere"}',
                    ]],
                ],
            ]],
        ], 200),
    ]);

    config(['gemini.api_key' => 'test-gemini-key']);

    $competitor = Competitor::query()->create(['name' => 'Rival', 'is_active' => true]);
    $keyword = CompetitorKeyword::query()->create([
        'competitor_id' => $competitor->id,
        'keyword' => 'arekere home nursing',
        'intent_type' => 'local',
    ]);

    SeoEntity::query()->create([
        'organization_name' => 'Medca Health Care',
        'hijack_strategy' => json_encode([
            (string) $keyword->id => [
                'keyword' => 'arekere home nursing',
                'meta_title' => 'Draft title',
                'hijack_priority' => 8,
            ],
        ], JSON_THROW_ON_ERROR),
    ]);

    (new AutonomousContentJob($keyword->id))->handle();

    $entity = SeoEntity::query()->latest('id')->first();
    $strategies = $entity->hijackStrategies();

    expect($strategies[(string) $keyword->id]['autonomous_content']['meta_title'] ?? null)
        ->toBe('Arekere Home Nursing | Medca')
        ->and($strategies[(string) $keyword->id]['autonomous_content']['status'] ?? null)->toBe('ready');
});

it('one click publish updates page and seo entity from hijack strategy', function () {
    if (! Schema::hasColumn('seo_entities', 'hijack_strategy')) {
        $this->markTestSkipped('Hijack strategy column missing.');
    }

    $page = Page::factory()->create([
        'slug' => 'arekere-nursing',
        'meta_title' => 'Old title',
    ]);

    SeoEntity::query()->create([
        'organization_name' => 'Medca Health Care',
        'hijack_strategy' => json_encode([
            '7' => [
                'keyword' => 'arekere nursing',
                'meta_title' => 'Base title',
                'meta_description' => 'Base description',
                'h1_suggestion' => 'Base H1',
                'autonomous_content' => [
                    'meta_title' => 'Arekere Nursing | Medca',
                    'meta_description' => 'Premium home nursing in Arekere.',
                    'h1' => 'Nursing Care in Arekere',
                    'status' => 'ready',
                ],
            ],
        ], JSON_THROW_ON_ERROR),
    ]);

    $result = app(HijackContentBridgeService::class)->applyAndPublish($page, '7');

    expect($result['page']->meta_title)->toBe('Arekere Nursing | Medca')
        ->and($result['page']->h1)->toBe('Nursing Care in Arekere');

    $entity = SeoEntity::query()->latest('id')->first();
    $strategies = $entity->hijackStrategies();
    expect($strategies['7']['applied_at'] ?? null)->not->toBeNull();
});

it('detects backlink gaps when competitors have domains medca lacks', function () {
    if (! Schema::hasTable('competitor_backlinks')) {
        $this->markTestSkipped('Backlink tables missing.');
    }

    $competitor = Competitor::query()->create([
        'name' => 'Rival Care',
        'website' => 'https://rival.example.com',
        'is_active' => true,
        'is_intercept_target' => true,
    ]);

    CompetitorBacklink::query()->create([
        'competitor_id' => $competitor->id,
        'referring_domain' => 'practo.com',
        'target_url' => 'https://rival.example.com',
        'discovery_method' => 'catalog',
        'status' => 'active',
        'last_checked_at' => now(),
    ]);

    SiteBacklink::query()->create([
        'referring_domain' => 'medcahealthcare.com',
        'source' => 'manual',
        'status' => 'active',
    ]);

    $gaps = app(BacklinkMonitorService::class)->gapDomains();

    expect($gaps)->toHaveCount(1)
        ->and($gaps->first()['domain'])->toBe('practo.com');
});

it('includes content health and backlink strength in readiness report', function () {
    GrowthReadinessReport::forget();

    $report = GrowthReadinessReport::build();

    expect($report)->toHaveKeys(['score_content', 'score_backlinks'])
        ->and(collect($report['health_row'])->pluck('id'))->toContain('content', 'backlinks')
        ->and($report['sections'])->toHaveKeys(['content', 'backlinks']);
});

it('shows backlink gap widget on war room tab', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => ['growth_center' => true],
    ]);

    $this->actingAs($user)
        ->get(route('growth-center.war-room'))
        ->assertOk()
        ->assertSeeText('Backlink gap intelligence');
});

it('site architect one click publish livewire updates page', function () {
    if (! Schema::hasColumn('seo_entities', 'hijack_strategy')) {
        $this->markTestSkipped('Hijack strategy column missing.');
    }

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::SITE_ARCHITECT])
            ->all(),
    ]);

    $page = Page::factory()->create(['slug' => 'hijack-target', 'focus_keywords' => ['blood test arekere']]);

    SeoEntity::query()->create([
        'organization_name' => 'Medca Health Care',
        'hijack_strategy' => json_encode([
            '12' => [
                'keyword' => 'blood test arekere',
                'meta_title' => 'Blood Test Arekere',
                'meta_description' => 'Book home blood tests.',
                'h1_suggestion' => 'Blood Tests in Arekere',
            ],
        ], JSON_THROW_ON_ERROR),
    ]);

    Livewire::actingAs($user)
        ->test(Pages::class)
        ->call('startEdit', $page->id)
        ->call('applyAndPublishHijackStrategy', '12')
        ->assertHasNoErrors();

    expect($page->fresh()->meta_title)->toBe('Blood Test Arekere');
});
