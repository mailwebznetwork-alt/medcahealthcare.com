<?php

use App\Models\Block;
use App\Models\Page;
use App\Models\Service;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Operations\ServiceSeoOwnership;

it('prevents managed block code mutation via block factory save path', function () {
    $block = Block::query()->where('block_slug', 'hero-home')->first()
        ?? Block::factory()->create(['block_slug' => 'hero-home', 'is_managed' => true, 'code' => "@include('blocks.home.hero-home')"]);

    $block->update(['is_managed' => true, 'code' => "@include('blocks.home.hero-home')", 'custom_css' => null]);

    $block->update([
        'custom_css' => '.test-managed { color: red; }',
        'is_active' => true,
    ]);

    expect($block->fresh()->code)->toBe("@include('blocks.home.hero-home')")
        ->and($block->fresh()->custom_css)->toContain('test-managed');
});

it('skips canonical service seo fields when linked page has meta', function () {
    $page = Page::withoutEvents(fn () => Page::factory()->create([
        'meta_title' => 'Page wins',
        'meta_description' => 'Page description',
    ]));

    $service = Service::factory()->create(['detail_page_id' => $page->id]);
    $service->seo()->updateOrCreate(
        ['service_id' => $service->id],
        [
            'meta_title' => 'Old service title',
            'meta_description' => 'Old service description',
            'h1' => 'Old H1',
        ]
    );

    expect(ServiceSeoOwnership::pageSeoOverridesService($page->fresh()))->toBeTrue();

    $controller = app(\App\Http\Controllers\Operations\Services\ServiceController::class);
    $method = new \ReflectionMethod($controller, 'syncSeo');
    $method->setAccessible(true);
    $method->invoke($controller, $service->fresh(), [
        'meta_title' => 'Attempt override',
        'meta_description' => 'Attempt override',
        'h1' => 'Attempt H1',
        'ai_context' => 'Updated AI context',
    ]);

    $service->refresh();
    expect($service->seo?->meta_title)->toBe('Old service title')
        ->and($service->seo?->ai_context)->toBe('Updated AI context');
});
