<?php

use App\Models\Block;
use App\Models\BlockPreset;
use App\Models\GlobalContentVariableSnapshot;
use App\Models\SectionLibraryItem;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Deployment\BlockPresetRepository;
use App\Services\Deployment\BlockSettingsEditor;
use App\Services\Deployment\DeploymentPackageValidator;
use App\Services\Deployment\GlobalContentVariableRepository;
use App\Services\Theme\ThemeConfigRepository;
use Database\Seeders\ThemePresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ThemePresetSeeder::class);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_05_31_120000_create_deployment_engine_tables.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_05_31_140000_phase85_supplemental_patch.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_05_31_160000_phase85_completion_patch.php']);
});

it('saves header configuration in branding draft', function () {
    $user = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Settings\AppearanceSettings::class)
        ->set('activeTab', 'header')
        ->set('header_config.show_top_bar', false)
        ->set('header_config.sticky_behavior', 'shrink_on_scroll')
        ->call('saveHeader')
        ->assertHasNoErrors();

    $config = app(ThemeConfigRepository::class)->draftHeaderConfiguration();
    expect($config['show_top_bar'])->toBeFalse()
        ->and($config['sticky_behavior'])->toBe('shrink_on_scroll');
});

it('exposes block presets admin route', function () {
    $user = User::factory()->create([
        'role' => 'manager',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($user)
        ->get(route('site-architect.block-presets.index'))
        ->assertSuccessful()
        ->assertSee(__('Templates'));
});

it('saves block media and section settings via block studio', function () {
    $user = User::factory()->create([
        'role' => 'editor',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    Block::query()->create([
        'block_slug' => 'hero-home',
        'block_name' => 'Hero',
        'block_type' => 'Hero',
        'code' => '<p>Hero</p>',
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\SiteArchitect\BlockStudio::class)
        ->set('block_slug', 'hero-home')
        ->set('media.desktop_image', 'images/hero.jpg')
        ->set('section.background_color', '#0a0f1c')
        ->call('saveDraft')
        ->assertHasNoErrors();

    $block = Block::query()->where('block_slug', 'hero-home')->first();
    expect($block->settings_json['media']['desktop_image'])->toBe('images/hero.jpg')
        ->and($block->settings_json['section']['background_color'])->toBe('#0a0f1c');
});

it('versions global content variables', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $repo = app(GlobalContentVariableRepository::class);
    $repo->sync(['company_name' => 'Version Test Co'], $user);

    $snapshot = $repo->createSnapshot($user);

    expect($snapshot)->toBeInstanceOf(GlobalContentVariableSnapshot::class)
        ->and($snapshot->payload_json['company_name'])->toBe('Version Test Co');
});

it('validates deployment package manifest', function () {
    $report = app(DeploymentPackageValidator::class)->validate([
        'format' => 'markonminds.deployment-package',
        'version' => '1.0.0',
        'style_pack' => ['slug' => 'healthcare_professional'],
        'global_content_variables' => [],
        'section_library' => [],
    ]);

    expect($report['valid'])->toBeTrue();
});

it('clones block presets via repository', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $preset = app(BlockPresetRepository::class)->save('Hero Style', 'Hero', 'hero-home', ['style_variant' => 'style_2'], $user);
    $clone = app(BlockPresetRepository::class)->clone($preset, 'Hero Style Copy', $user);

    expect($clone->name)->toBe('Hero Style Copy')
        ->and($clone->settings_json['style_variant'])->toBe('style_2');
});

it('previews block html via block settings editor', function () {
    Block::query()->create([
        'block_slug' => 'cta-test',
        'block_name' => 'CTA',
        'block_type' => 'CTA',
        'code' => '<p>CTA content</p>',
        'is_active' => true,
    ]);

    $html = app(BlockSettingsEditor::class)->previewHtml(Block::query()->where('block_slug', 'cta-test')->first());

    expect($html)->toContain('CTA content');
});

it('restricts deployment packages to package roles', function () {
    $editor = User::factory()->create([
        'role' => 'editor',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($editor)
        ->get(route('site-architect.deployment-packages.index'))
        ->assertForbidden();
});

it('allows admin on deployment packages route', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($admin)
        ->get(route('site-architect.deployment-packages.index'))
        ->assertSuccessful();
});

it('renders section library shell without blade parse errors', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    config(['platform_composition.section_library_deprecated' => true]);

    $this->actingAs($admin)
        ->get(route('site-architect.section-library.index'))
        ->assertSuccessful()
        ->assertSee('Deprecated', false);
});

it('section library supports clone and delete', function () {
    $user = User::factory()->create(['role' => 'manager', 'module_access' => ModuleAccess::defaultGrants()]);

    $section = SectionLibraryItem::query()->create([
        'slug' => 'custom-intro',
        'name' => 'Custom Intro',
        'blocks_json' => [['slug' => 'hero-home']],
        'is_builtin' => false,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\SiteArchitect\SectionLibrary::class)
        ->call('cloneSection', 'custom-intro')
        ->assertSet('statusMessage', fn ($msg) => str_contains((string) $msg, 'cloned') || str_contains((string) $msg, 'Cloned'));

    expect(SectionLibraryItem::query()->count())->toBe(2);

    Livewire::actingAs($user)
        ->test(\App\Livewire\SiteArchitect\SectionLibrary::class)
        ->call('deleteSection', 'custom-intro');

    expect(SectionLibraryItem::query()->where('slug', 'custom-intro')->exists())->toBeFalse();
});
