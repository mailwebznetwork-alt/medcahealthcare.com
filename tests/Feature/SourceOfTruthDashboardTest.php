<?php

use App\Livewire\System\SourceOfTruth;
use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\User;
use App\ModuleAccess;
use Database\Seeders\ThemePresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ThemePresetSeeder::class);
});

function sourceOfTruthAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'module_access' => array_merge(ModuleAccess::defaultGrants(), ['settings' => true]),
    ]);
}

it('exposes source of truth route for settings module grant', function () {
    $user = sourceOfTruthAdmin();

    $this->actingAs($user)
        ->get(route('system.source-of-truth'))
        ->assertSuccessful()
        ->assertSee(__('Source of Truth'), false)
        ->assertSee(__('Registry rows'), false)
        ->assertSee(__('Governance'), false)
        ->assertSee(__('Sync registry'), false);
});

it('shows source of truth tab between overview and integrations', function () {
    $user = sourceOfTruthAdmin();

    $this->actingAs($user)
        ->get(route('system.source-of-truth'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            __('Overview'),
            __('Source of Truth'),
            __('Integrations'),
        ], false);
});

it('renders registry metrics from the dashboard service', function () {
    $page = Page::factory()->create([
        'slug' => 'source-of-truth-test-page',
        'page_source' => 'manual',
    ]);

    PageRegistry::query()->create([
        'page_id' => $page->id,
        'entity_type' => 'page',
        'entity_id' => $page->id,
        'registry_key' => 'page:'.$page->slug,
        'page_category' => 'other',
        'owner' => 'site_architect',
        'source' => 'manual',
        'public_path' => '/'.$page->slug,
        'is_listed' => true,
    ]);

    $component = Livewire::actingAs(sourceOfTruthAdmin())
        ->test(SourceOfTruth::class);

    expect($component->get('report.metrics.registry_rows'))->toBeGreaterThanOrEqual(1);
    expect($component->get('report.metrics.pages'))->toBeGreaterThanOrEqual(1);
    expect(PageRegistry::query()->where('registry_key', 'page:'.$page->slug)->exists())->toBeTrue();
});

it('can sync registry from the dashboard', function () {
    $page = Page::factory()->create([
        'slug' => 'sync-registry-dashboard-page',
        'page_source' => 'generated',
    ]);

    Livewire::actingAs(sourceOfTruthAdmin())
        ->test(SourceOfTruth::class)
        ->call('syncRegistry')
        ->assertSet('flashType', 'success');

    expect(PageRegistry::query()->where('registry_key', 'page:'.$page->slug)->exists())->toBeTrue();
});

it('can purge orphan registry rows from the dashboard', function () {
    PageRegistry::query()->create([
        'page_id' => null,
        'entity_type' => 'location',
        'entity_id' => 999999,
        'registry_key' => 'location:orphan-service:000000',
        'page_category' => 'location',
        'owner' => 'operations_location_matrix',
        'source' => 'generated',
        'public_path' => '/services/orphan-service/in/000000',
        'is_listed' => false,
    ]);

    Livewire::actingAs(sourceOfTruthAdmin())
        ->test(SourceOfTruth::class)
        ->call('purgeOrphans')
        ->assertSet('flashType', 'success');

    expect(PageRegistry::query()->where('registry_key', 'location:orphan-service:000000')->exists())->toBeFalse();
});
