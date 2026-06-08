<?php

use App\Livewire\SiteArchitect\Pages;
use App\Models\Page;
use App\Models\User;
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

it('exposes sidebar groups with original screen names', function () {
    $labels = collect(SiteArchitectNavigation::sidebarGroups())
        ->flatMap(fn (array $g) => collect($g['items'])->pluck('label'))
        ->all();

    expect($labels)->toContain('Pages', 'Blogs', 'Navigation', 'Media')
        ->and($labels)->toContain('Section Content', 'Blocks Factory', 'Style Templates');
});

it('renders site architect workspace with left sidebar navigation', function () {
    $this->get(route('site-architect.pages.index'))
        ->assertSuccessful()
        ->assertSee('Site Architect')
        ->assertSee('Content')
        ->assertSee('id="site-architect-sidebar"', false);
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

it('includes operations links in sidebar when module access allows', function () {
    $operations = collect(SiteArchitectSidebarState::groups())
        ->firstWhere('key', 'operations');

    expect($operations)->not->toBeNull();

    $names = collect($operations['items'])->pluck('label')->all();

    expect($names)->toContain('Source of Truth');
});

it('sidebar default expanded groups include content and sections', function () {
    expect(SiteArchitectSidebarState::defaultExpanded())->toContain('content', 'sections');
});
