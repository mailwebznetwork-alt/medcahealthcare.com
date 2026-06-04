<?php

use App\Models\Page;
use App\Services\Blocks\BlockTemplateSyncService;
use App\Services\ContentParser;
use App\Services\Pages\MarketingPageBlockPatcher;
use App\Services\Public\PageRenderContextRegistrar;
use Database\Seeders\MedcaLaunchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers near-you-home on the home page composition', function () {
    $this->seed(MedcaLaunchSeeder::class);

    $home = Page::query()->where('slug', 'home')->firstOrFail();

    expect($home->content)->toContain('{{block:near-you-home}}');
});

it('renders near-you copy from block content schema', function () {
    app(BlockTemplateSyncService::class)->sync(categories: ['home']);

    $page = Page::query()->create([
        'slug' => 'home',
        'title' => 'Home',
        'content' => "{{block:hero-home}}\n{{block:near-you-home}}\n{{block:cta-home}}",
        'is_active' => true,
    ]);

    app(PageRenderContextRegistrar::class)->register($page);

    $html = ContentParser::parse($page->content);

    expect($html)->toContain('Near You')
        ->and($html)->toContain('data-section="near-you"');
});

it('inserts near-you blocks on home and locations via patcher', function () {
    app(\App\Services\Blocks\BlockTemplateSyncService::class)->sync(
        slugs: ['near-you-home', 'near-you-locations'],
    );

    Page::query()->create([
        'slug' => 'locations',
        'title' => 'Locations',
        'content' => "{{block:hero-locations}}\n{{block:locations-coverage}}",
        'is_active' => true,
    ]);

    expect(app(\App\Services\Pages\MarketingPageBlockPatcher::class)->ensureRequiredNearYouBlocks())
        ->toMatchArray(['home' => false, 'locations' => true]);

    expect(Page::query()->where('slug', 'locations')->value('content'))
        ->toContain('{{block:near-you-locations}}');
});

it('inserts near-you-home without duplicating when missing from legacy home content', function () {
    app(BlockTemplateSyncService::class)->sync(categories: ['home']);

    Page::query()->create([
        'slug' => 'home',
        'title' => 'Home',
        'content' => "{{block:hero-home}}\n{{block:services-overview-home}}\n{{block:locations-overview-home}}",
        'is_active' => true,
    ]);

    expect(app(MarketingPageBlockPatcher::class)->ensureHomeNearYouBlock())->toBeTrue();

    $content = Page::query()->where('slug', 'home')->value('content');
    expect($content)->toContain('{{block:near-you-home}}')
        ->and(substr_count($content, 'near-you-home'))->toBe(1);

    expect(app(MarketingPageBlockPatcher::class)->ensureHomeNearYouBlock())->toBeFalse();
});

it('exposes vision fields on the about body block', function () {
    app(BlockTemplateSyncService::class)->sync(categories: ['about']);

    $page = Page::query()->create([
        'slug' => 'about-us',
        'title' => 'About',
        'content' => '{{block:body-about}}',
        'is_active' => true,
    ]);

    app(PageRenderContextRegistrar::class)->register($page);

    $html = ContentParser::parse($page->content);

    expect($html)->toContain('Our vision')
        ->and($html)->toContain('Our mission');
});
