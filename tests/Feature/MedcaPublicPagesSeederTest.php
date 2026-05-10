<?php

use App\Models\Block;
use App\Models\Page;
use App\Models\SiteNavigationItem;
use Database\Seeders\MedcaPublicPagesSeeder;

beforeEach(function (): void {
    $this->seed(MedcaPublicPagesSeeder::class);
});

it('seeds the five public marketing pages as active records', function () {
    foreach (['home', 'about-us', 'services', 'locations', 'contact'] as $slug) {
        $page = Page::query()->where('slug', $slug)->first();

        expect($page)->not->toBeNull();
        expect($page->is_active)->toBeTrue();
        expect($page->title)->not->toBeEmpty();
    }
});

it('seeds page content composed entirely of editable block tokens', function () {
    foreach (Page::query()->whereIn('slug', ['home', 'about-us', 'services', 'locations', 'contact'])->get() as $page) {
        $tokens = Page::parseContentTokens($page->content);

        expect($tokens)->not->toBeEmpty();

        foreach ($tokens as $token) {
            expect($token['type'])->toBe('block');

            $block = Block::query()->where('block_slug', $token['slug'])->first();

            expect($block)->not->toBeNull();
            expect($block->is_active)->toBeTrue();
            expect($block->code)->not->toBeEmpty();
        }
    }
});

it('seeds the header navigation in the expected order', function () {
    $links = SiteNavigationItem::query()
        ->where('zone', SiteNavigationItem::ZONE_HEADER)
        ->orderBy('sort_order')
        ->with('page')
        ->get();

    expect($links)->toHaveCount(5);

    $expected = ['home', 'about-us', 'services', 'locations', 'contact'];
    foreach ($expected as $i => $slug) {
        expect($links[$i]->page->slug)->toBe($slug);
    }
});

it('renders every seeded marketing page on its public URL with rendered block markup', function () {
    $cases = [
        ['url' => '/', 'expectIn' => 'Premium home healthcare'],
        ['url' => '/p/about-us', 'expectIn' => 'Doctor-led, family-centred home healthcare'],
        ['url' => '/p/services', 'expectIn' => 'Hospital-grade care at home'],
        ['url' => '/p/locations', 'expectIn' => 'Where Medca cares'],
        ['url' => '/p/contact', 'expectIn' => 'Talk to a Medca care advisor'],
    ];

    foreach ($cases as $case) {
        $this->get($case['url'])
            ->assertSuccessful()
            ->assertSee($case['expectIn'], false);
    }
});

it('exposes seeded pages in the public header navigation, with home routed to /', function () {
    $response = $this->get('/')->assertSuccessful();
    foreach (['Home', 'About Us', 'Services', 'Locations', 'Contact Us'] as $label) {
        $response->assertSee($label, false);
    }

    $response->assertSee('href="'.url('/').'"', false);
    $response->assertSee('href="'.url('/p/about-us').'"', false);
});

it('is idempotent — re-running keeps row counts steady', function () {
    $pageCount = Page::query()->count();
    $blockCount = Block::query()->count();
    $navCount = SiteNavigationItem::query()->count();

    $this->seed(MedcaPublicPagesSeeder::class);
    $this->seed(MedcaPublicPagesSeeder::class);

    expect(Page::query()->count())->toBe($pageCount);
    expect(Block::query()->count())->toBe($blockCount);
    expect(SiteNavigationItem::query()->count())->toBe($navCount);
});

it('lets edits to a seeded block flow through to the rendered page', function () {
    $block = Block::query()->where('block_slug', 'hero-home')->firstOrFail();

    $marker = 'Edited-Hero-Marker-'.uniqid();
    $block->update(['code' => '<section>'.$marker.'</section>']);

    $this->get('/')
        ->assertSuccessful()
        ->assertSee($marker, false);
});

it('keeps a page editable as a structured list of block tokens via the Page editor API', function () {
    $page = Page::query()->where('slug', 'home')->firstOrFail();

    $parts = Page::parseContentTokens($page->content);

    expect($parts)->not->toBeEmpty();

    array_unshift($parts, ['type' => 'block', 'slug' => 'cta-home']);
    $page->update(['content' => Page::buildContentFromParts($parts)]);

    $reparsed = Page::parseContentTokens($page->fresh()->content);

    expect($reparsed[0]['slug'])->toBe('cta-home');
});
