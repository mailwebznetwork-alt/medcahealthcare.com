<?php

use App\Livewire\SiteArchitect\Pages;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\User;
use App\ModuleAccess;
use App\Support\SiteArchitectNavigation;
use App\Support\SiteArchitectSidebarState;
use Database\Seeders\MedcaLaunchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(MedcaLaunchSeeder::class);
    $this->actingAs(User::factory()->create(['role' => 'admin']));
});

it('exposes sidebar groups with approved screen names', function () {
    $labels = collect(SiteArchitectNavigation::sidebarGroups())
        ->flatMap(fn (array $g) => collect($g['items'])->pluck('label'))
        ->all();

    expect($labels)->toContain('Pages', 'Blogs', 'Navigation', 'Media')
        ->and($labels)->toContain('Section Content', 'Blocks Factory', 'Style Templates')
        ->and($labels)->toContain('Blueprint Builder', 'Packages', 'Module Builder', 'Legacy Sections', 'Locations');
});

it('does not expose duplicate operations links in site architect navigation', function () {
    $labels = collect(SiteArchitectNavigation::sidebarGroups())
        ->flatMap(fn (array $g) => collect($g['items'])->pluck('label'))
        ->all();

    expect($labels)->not->toContain('Pincodes', 'Services', 'Categories', 'Sub Services')
        ->and($labels)->not->toContain('Source of Truth', 'Content Safety', 'Theme Status', 'Audit Logs', 'Service Locations');

    $keys = collect(SiteArchitectNavigation::sidebarGroups())->pluck('key')->all();

    expect($keys)->not->toContain('operations');
});

it('renders site architect workspace without a second sidebar', function () {
    $this->get(route('site-architect.pages.index'))
        ->assertSuccessful()
        ->assertDontSee('id="site-architect-sidebar"', false)
        ->assertDontSee('aria-controls="site-architect-sidebar"', false);
});

it('renders site architect nested navigation in the primary sidebar', function () {
    $this->get(route('site-architect.pages.index'))
        ->assertSuccessful()
        ->assertSee('data-sidebar-module="site_architect"', false)
        ->assertSee(__('Content'), false)
        ->assertSee(__('Building'), false)
        ->assertSee(__('Pages'), false);
});

it('tracks bulk row selection on pages list', function () {
    $page = Page::query()->firstOrFail();

    Livewire::test(Pages::class)
        ->call('toggleBulkRow', $page->id)
        ->assertSet('bulkSelectedIds', [$page->id])
        ->call('deselectAllRows')
        ->assertSet('bulkSelectedIds', []);
});

it('requires DELETE confirmation for bulk delete preview', function () {
    $page = Page::query()->firstOrFail();

    Livewire::test(Pages::class)
        ->call('toggleBulkRow', $page->id)
        ->call('openBulkAction', 'delete')
        ->assertSet('bulkModalOpen', true)
        ->set('bulkDeleteConfirmText', 'NOPE')
        ->call('confirmBulkAction')
        ->assertHasErrors('bulkDeleteConfirmText');
});

it('reorders page content parts via drag sync method', function () {
    $page = Page::query()->where('slug', 'home')->firstOrFail();

    $component = Livewire::test(Pages::class)->call('startEdit', $page->id);
    $before = $component->get('contentParts');
    $count = count($before);

    if ($count < 2) {
        expect($count)->toBeGreaterThan(0);

        return;
    }

    $component
        ->call('syncContentPartsOrder', ['1', '0', ...range(2, $count - 1)])
        ->assertSet('contentParts.0', $before[1]);
});

it('sidebar default expanded groups include content and building', function () {
    expect(SiteArchitectSidebarState::defaultExpanded())->toContain('content', 'building');
});

it('selects and clears all pin codes in the page geo checklist', function () {
    PinCode::factory()->inactive()->create();
    $activeCount = PinCode::query()->where('is_active', true)->count();

    Livewire::test(Pages::class)
        ->call('startCreate')
        ->assertSet('selectedPinIds', [])
        ->call('selectAllPinCodes')
        ->assertCount('selectedPinIds', $activeCount)
        ->call('clearAllPinCodes')
        ->assertSet('selectedPinIds', [])
        ->assertSet('pinPivot', []);
});

it('selects only filtered pin codes in the page geo checklist', function () {
    $target = PinCode::factory()->create([
        'pincode' => '569991',
        'area_name' => 'Zephyr Test Area',
        'city' => 'Bangalore',
        'is_active' => true,
    ]);
    PinCode::factory()->create([
        'pincode' => '569992',
        'area_name' => 'Other Test Area',
        'city' => 'Bangalore',
        'is_active' => true,
    ]);

    Livewire::test(Pages::class)
        ->call('startCreate')
        ->set('pinCodeFilter', 'Zephyr Test Area')
        ->call('selectFilteredPinCodes')
        ->assertCount('selectedPinIds', 1)
        ->assertSet('selectedPinIds.0', $target->id);
});

it('preserves route access for site architect features across roles', function (string $role, array $routes, array $forbiddenLabels) {
    $user = User::factory()->create([
        'role' => $role,
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($user);

    foreach ($routes as $route) {
        $this->followingRedirects()->get(route($route))->assertSuccessful();
    }

    $labels = collect(SiteArchitectNavigation::sidebarGroups($user))
        ->flatMap(fn (array $g) => collect($g['items'])->pluck('label'))
        ->all();

    foreach ($forbiddenLabels as $label) {
        expect($labels)->not->toContain($label);
    }
})->with([
    'editor' => [
        'editor',
        [
            'site-architect.pages.index',
            'site-architect.blogs.index',
            'site-architect.navigation.index',
            'site-architect.media.index',
            'site-architect.block-studio.index',
            'site-architect.presets.index',
        ],
        ['Blueprint Builder', 'Packages', 'Module Builder', 'Legacy Sections', 'Locations', 'Blocks Factory'],
    ],
    'manager' => [
        'manager',
        [
            'site-architect.pages.index',
            'site-architect.blogs.index',
            'site-architect.navigation.index',
            'site-architect.media.index',
            'site-architect.block-studio.index',
            'site-architect.block-factory.index',
            'site-architect.presets.index',
        ],
        ['Blueprint Builder', 'Packages', 'Module Builder', 'Legacy Sections', 'Locations'],
    ],
    'admin' => [
        'admin',
        [
            'site-architect.pages.index',
            'site-architect.blogs.index',
            'site-architect.navigation.index',
            'site-architect.media.index',
            'site-architect.block-studio.index',
            'site-architect.block-factory.index',
            'site-architect.presets.index',
            'site-architect.blueprint-builder.index',
            'site-architect.deployment-packages.index',
            'site-architect.modules.index',
            'site-architect.sections.index',
        ],
        [],
    ],
]);
