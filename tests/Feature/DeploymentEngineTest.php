<?php

use App\Models\Block;
use App\Models\Page;
use App\Models\User;
use App\ModuleAccess;
use App\Policies\DeploymentEnginePolicy;
use App\Services\Deployment\BlockSettingsResolver;
use App\Services\Deployment\BlueprintPageGenerator;
use App\Services\Deployment\BlueprintRegistry;
use App\Services\Deployment\StylePackRegistry;
use App\Services\Theme\ThemeCssVariableBuilder;
use App\Services\Theme\ThemeTokenRegistry;
use Database\Seeders\ThemePresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ThemePresetSeeder::class);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_05_31_120000_create_deployment_engine_tables.php']);
});

it('lists industry blueprints from config', function () {
    expect(app(BlueprintRegistry::class)->find('home_healthcare'))->not->toBeNull()
        ->and(app(BlueprintRegistry::class)->forIndustry('healthcare'))->not->toBeEmpty();
});

it('generates standard pages with block tokens from blueprint', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $result = app(BlueprintPageGenerator::class)->generate(
        'home_healthcare',
        'healthcare_professional',
        'clinical_blue',
        'contained',
        $user,
    );

    $home = Page::query()->where('slug', 'home')->first();
    expect($home)->not->toBeNull()
        ->and($home->content)->toContain('{{block:hero-healthcare}}')
        ->and($home->block_overrides_json)->toHaveKey('hero-healthcare')
        ->and($result['generation']->blueprint_slug)->toBe('home_healthcare');
});

it('resolves block style variant from style pack', function () {
    Block::query()->create([
        'block_slug' => 'hero-home',
        'block_name' => 'Hero Home',
        'block_type' => 'Hero',
        'code' => '@include("blocks.home.hero-home")',
        'is_active' => true,
    ]);

    $vars = app(BlockSettingsResolver::class)->renderVariables(
        'hero-home',
        null,
        null,
        'healthcare_premium',
    );

    expect($vars['blockStyleVariant'])->toBe('style_2');
});

it('maps shape tokens to css variables', function () {
    $defaults = app(ThemeTokenRegistry::class)->defaultShapeTokens();
    $vars = app(ThemeCssVariableBuilder::class)->shapeVariables($defaults);

    expect($vars['--medca-radius-md'])->toBe('12px');
});

it('restricts blueprint builder to generator roles', function () {
    $editor = User::factory()->create(['role' => 'editor']);
    $admin = User::factory()->create(['role' => 'admin']);

    expect(app(DeploymentEnginePolicy::class)->useBlueprintBuilder($editor))->toBeFalse()
        ->and(app(DeploymentEnginePolicy::class)->useBlueprintBuilder($admin))->toBeTrue();
});

it('allows managers to open blueprint builder route', function () {
    $user = User::factory()->create([
        'role' => 'manager',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($user)
        ->get(route('site-architect.blueprint-builder.index'))
        ->assertSuccessful()
        ->assertSee(__('Blueprint Builder'));
});

it('blocks editor from blueprint builder route', function () {
    $user = User::factory()->create([
        'role' => 'editor',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($user)
        ->get(route('site-architect.blueprint-builder.index'))
        ->assertForbidden();
});

it('exposes style pack assignments in registry', function () {
    $assignments = app(StylePackRegistry::class)->assignments('consultancy_corporate');

    expect($assignments['hero'])->toBe('style_4');
});
