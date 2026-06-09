<?php

use App\Enums\PageLayoutMode;
use App\Models\Block;
use App\Models\Page;
use App\Models\Vacancy;
use App\Services\ContentParser;

it('wraps canonical marketing blocks in the public content shell', function () {
    $html = ContentParser::renderBlockCode(<<<'BLADE'
<x-public.section class="bg-white" data-test="shell-section">
    <p>Section body copy</p>
</x-public.section>
BLADE);

    expect($html)
        ->toContain('max-w-6xl')
        ->toContain('data-test="shell-section"', false)
        ->toContain('Section body copy', false);
});

it('wraps hero blocks in full-bleed with aligned inner shell', function () {
    $html = ContentParser::renderBlockCode(<<<'BLADE'
<x-public.hero class="bg-medca-navy text-white" data-test="shell-hero">
    <h1>Hero headline</h1>
</x-public.hero>
BLADE);

    expect($html)
        ->toContain('max-w-6xl')
        ->toContain('data-test="shell-hero"', false)
        ->toContain('Hero headline', false);
});

it('keeps canvas main full width while blocks supply the content shell', function () {
    Block::query()->updateOrCreate(
        ['block_slug' => 'layout-shell-test'],
        [
            'block_name' => 'Layout shell test',
            'code' => '<x-public.section data-test="canvas-shell"><p>Canvas aligned</p></x-public.section>',
            'is_active' => true,
        ]
    );

    Page::query()->updateOrCreate(
        ['slug' => 'layout-shell-test'],
        [
            'title' => 'Layout shell test',
            'content' => '{{block:layout-shell-test}}',
            'is_active' => true,
            'layout_mode' => PageLayoutMode::Canvas,
        ]
    );

    $response = $this->get('/p/layout-shell-test')->assertSuccessful();

    preg_match(
        '/<main[^>]*id="main-content"[^>]*class="([^"]*)"/',
        $response->getContent(),
        $matches
    );

    expect($matches[1] ?? '')
        ->toContain('w-full')
        ->not->toContain('max-w-6xl');

    $response
        ->assertSee('data-test="canvas-shell"', false)
        ->assertSee('max-w-6xl', false)
        ->assertSee('Canvas aligned', false);
});

it('aligns the careers hub hero and listing with the header content width', function () {
    Block::query()->updateOrCreate(
        ['block_slug' => 'hero-careers'],
        [
            'block_name' => 'Careers hero',
            'code' => "@include('careers.partials.hub-hero')",
            'is_active' => true,
        ]
    );

    Block::query()->updateOrCreate(
        ['block_slug' => 'careers-open-roles'],
        [
            'block_name' => 'Careers open roles',
            'code' => "@include('careers.partials.open-roles-listing', ['vacancies' => \$vacancies ?? collect()])",
            'is_active' => true,
        ]
    );

    Page::query()->updateOrCreate(
        ['slug' => 'careers'],
        [
            'title' => 'Careers',
            'content' => "{{block:hero-careers}}\n{{block:careers-open-roles}}",
            'is_active' => true,
            'layout_mode' => PageLayoutMode::Canvas,
        ]
    );

    Vacancy::factory()->published()->create(['title' => 'Layout Aligned Role']);

    $html = $this->get('/careers')->assertSuccessful()->getContent();

    expect(substr_count($html, 'max-w-6xl'))->toBeGreaterThanOrEqual(2);
    expect($html)->toContain('Layout Aligned Role');
});

it('aligns the near-you partial with the public content shell', function () {
    $html = view('public.partials.near-you-services', [
        'categories' => collect(),
        'pincode' => null,
        'pinCodeRecord' => null,
        'locationRequired' => false,
    ])->render();

    expect($html)->toContain('max-w-6xl');
});

