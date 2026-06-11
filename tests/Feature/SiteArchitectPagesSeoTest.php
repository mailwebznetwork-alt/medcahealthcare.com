<?php

use App\Models\Blog;
use App\Models\Page;
use App\Models\SiteSlugRedirect;
use Illuminate\Support\Str;

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

it('lists active CMS pages in the pages segment sitemap', function () {
    Page::factory()->create([
        'slug' => 'sitemap-cms-page',
        'is_active' => true,
    ]);

    $this->get('/sitemap.xml')
        ->assertSuccessful()
        ->assertSee(url('/sitemap-static-pages.xml'));

    $this->get('/sitemap-static-pages.xml')
        ->assertSuccessful()
        ->assertSee(url('/p/sitemap-cms-page'));
});

it('exposes a sitemap index and image entries for published blogs with artwork', function () {
    Blog::query()->create([
        'uuid' => (string) Str::uuid(),
        'title' => 'Indexed post',
        'slug' => 'indexed-post',
        'featured_image' => 'https://example.test/assets/hero.jpg',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->get('/sitemap.xml')->assertSuccessful()->assertSee(url('/sitemap-images.xml'));

    $this->get('/sitemap-blogs.xml')
        ->assertSuccessful()
        ->assertSee(url('/blog/indexed-post'));

    $this->get('/sitemap-images.xml')
        ->assertSuccessful()
        ->assertSee(url('/blog/indexed-post'))
        ->assertSee('https://example.test/assets/hero.jpg');
});
