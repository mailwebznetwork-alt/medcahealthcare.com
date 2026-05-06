<?php

use App\Models\Page;
use App\Models\SiteSlugRedirect;

it('renders page canonical, robots, og, and json-ld on public routes', function () {
    Page::factory()->create([
        'slug' => 'seo-test-page',
        'is_active' => true,
        'canonical_url' => 'https://example.test/canonical',
        'robots_meta' => 'noindex, nofollow',
        'og_image' => 'https://example.test/og.jpg',
        'og_image_alt' => 'Alt text',
        'schema_json' => [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Test',
        ],
    ]);

    $response = $this->get('/p/seo-test-page');
    $response->assertSuccessful()
        ->assertSee('rel="canonical" href="https://example.test/canonical"', false)
        ->assertSee('name="robots" content="noindex, nofollow"', false)
        ->assertSee('property="og:image" content="https://example.test/og.jpg"', false)
        ->assertSee('property="og:image:alt"', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('"@type":"WebPage"', false);
});

it('301 redirects an old slug when a site slug redirect exists', function () {
    Page::factory()->create([
        'slug' => 'new-slug',
        'is_active' => true,
    ]);

    SiteSlugRedirect::query()->create([
        'from_slug' => 'old-slug',
        'to_slug' => 'new-slug',
    ]);

    $this->get('/p/old-slug')
        ->assertRedirect(route('pages.public', ['slug' => 'new-slug']));
});

it('lists active CMS pages in the generated sitemap', function () {
    Page::factory()->create([
        'slug' => 'sitemap-cms-page',
        'is_active' => true,
    ]);

    $this->get('/sitemap.xml')
        ->assertSuccessful()
        ->assertSee(url('/p/sitemap-cms-page'));
});
