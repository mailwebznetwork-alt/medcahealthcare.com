<?php

use App\Livewire\SiteArchitect\BlueprintBuilder;
use App\Models\Block;
use App\Models\Page;
use App\Models\ThemeConfiguration;
use App\Models\User;
use App\Services\Content\ContentRenderContext;
use App\Services\ContentParser;
use App\Services\Deployment\BlueprintPageGenerator;
use App\Services\Deployment\StylePackResolver;
use App\Services\Public\PageRenderContextRegistrar;
use App\Services\Theme\ThemeConfigRepository;
use Database\Seeders\ThemePresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ThemePresetSeeder::class);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_05_31_120000_create_deployment_engine_tables.php']);
});

it('registers current page and style pack via shared registrar', function () {
    $page = Page::query()->create([
        'slug' => 'about',
        'title' => 'About',
        'content' => '<p>About</p>',
        'is_active' => true,
        'deployment_meta_json' => ['style_pack' => 'healthcare_premium'],
    ]);

    app(PageRenderContextRegistrar::class)->register($page);

    $context = app(ContentRenderContext::class)->all();

    expect($context['currentPage'])->toBeInstanceOf(Page::class)
        ->and($context['currentPage']->id)->toBe($page->id)
        ->and($context['stylePackSlug'])->toBe('healthcare_premium');
});

it('applies block overrides on public home route', function () {
    Block::query()->create([
        'block_slug' => 'hero-home',
        'block_name' => 'Hero Home',
        'block_type' => 'Hero',
        'code' => '@include("blocks.home.hero-home")',
        'is_active' => true,
    ]);

    Page::query()->create([
        'slug' => 'home',
        'title' => 'Home',
        'content' => '{{block:hero-home}}',
        'is_active' => true,
        'block_overrides_json' => [
            'hero-home' => ['style_variant' => 'style_2'],
        ],
        'deployment_meta_json' => ['style_pack' => 'healthcare_premium'],
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('medca-block--style-2', false);
});

it('promotes draft style pack to active on theme publish', function () {
    $user = User::factory()->create(['role' => 'super_admin']);
    $config = ThemeConfiguration::current();
    $config->update(['draft_style_pack' => 'consultancy_corporate']);

    app(ThemeConfigRepository::class)->publishDraft($user);

    $config->refresh();

    expect($config->active_style_pack)->toBe('consultancy_corporate')
        ->and($config->draft_style_pack)->toBeNull();
});

it('renders whitelisted section styles on block wrapper', function () {
    $html = ContentParser::renderBlockCodeWithVariables(
        '<p>Section test</p>',
        0,
        null,
        'section-demo',
        [
            'blockSection' => [
                'background_color' => '#0a0f1c',
                'padding' => '24px',
                'visibility_mobile' => false,
            ],
            'blockStyleClass' => 'medca-block--style-1',
        ],
    );

    expect($html)
        ->toContain('background-color:#0a0f1c')
        ->toContain('padding:24px')
        ->toContain('medca-block--hide-mobile');
});

it('renders block media urls in hero output', function () {
    $html = ContentParser::renderBlockCodeWithVariables(
        '@include("blocks.home.hero-home")',
        0,
        null,
        'hero-home',
        [
            'blockMedia' => [
                'desktop_image' => 'https://cdn.example.test/hero-desktop.jpg',
                'mobile_image' => 'https://cdn.example.test/hero-mobile.jpg',
            ],
        ],
    );

    expect($html)
        ->toContain('--hero-bg-desktop')
        ->toContain('https://cdn.example.test/hero-desktop.jpg')
        ->toContain('--hero-bg-mobile')
        ->toContain('https://cdn.example.test/hero-mobile.jpg');
});

it('caches block lookups within a single parse request', function () {
    Block::query()->create([
        'block_slug' => 'cache-demo',
        'block_name' => 'Cache Demo',
        'block_type' => 'Hero',
        'code' => '<p>Cached</p>',
        'is_active' => true,
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    ContentParser::parse('{{block:cache-demo}}{{block:cache-demo}}');

    $blockQueries = collect(DB::getQueryLog())->filter(
        fn (array $entry): bool => str_contains(strtolower($entry['query']), 'blocks')
    );

    expect($blockQueries)->toHaveCount(1);
});

it('activates generated pages only when operator opts in', function () {
    $user = User::factory()->create(['role' => 'admin']);

    app(BlueprintPageGenerator::class)->generate(
        'home_healthcare',
        'healthcare_professional',
        'clinical_blue',
        'contained',
        $user,
        activatePages: false,
    );

    expect(Page::query()->where('slug', 'home')->value('is_active'))->toBeFalsy();

    app(BlueprintPageGenerator::class)->generate(
        'home_healthcare',
        'healthcare_professional',
        'clinical_blue',
        'contained',
        $user,
        activatePages: true,
    );

    expect(Page::query()->where('slug', 'home')->value('is_active'))->toBeTruthy();
});

it('previews style pack via session without publishing', function () {
    $user = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($user)
        ->test(BlueprintBuilder::class)
        ->set('style_pack_slug', 'consultancy_corporate')
        ->call('previewStylePack');

    expect(session(config('deployment_engine.preview_session_keys.style_pack')))
        ->toBe('consultancy_corporate');

    Livewire::actingAs($user)
        ->test(BlueprintBuilder::class)
        ->call('clearStylePackPreview');

    expect(session(config('deployment_engine.preview_session_keys.style_pack')))->toBeNull();
});

it('renders header search link when configuration enabled', function () {
    $user = User::factory()->create(['role' => 'super_admin']);
    $repo = app(ThemeConfigRepository::class);
    $branding = $repo->draftBranding();
    $branding['header_config'] = array_merge(
        $repo->defaultHeaderConfiguration(),
        ['show_search' => true],
    );
    $repo->saveDraftBranding($branding, $user);
    session(['theme_preview_public' => true]);

    $this->get('/')
        ->assertSuccessful()
        ->assertSee(__('Search'), false);
});

it('renders page gtm snippet when populated', function () {
    Page::query()->create([
        'slug' => 'gtm-test',
        'title' => 'GTM Test',
        'content' => '<p>Test</p>',
        'is_active' => true,
        'gtm_code' => '<!-- GTM-TEST-SNIPPET -->',
    ]);

    $this->get('/p/gtm-test')
        ->assertSuccessful()
        ->assertSee('GTM-TEST-SNIPPET', false);
});

it('resolves active style pack from published theme after publish', function () {
    $user = User::factory()->create(['role' => 'super_admin']);
    ThemeConfiguration::current()->update(['draft_style_pack' => 'healthcare_premium']);
    app(ThemeConfigRepository::class)->publishDraft($user);

    $slug = app(StylePackResolver::class)->activeSlug();

    expect($slug)->toBe('healthcare_premium');
});
