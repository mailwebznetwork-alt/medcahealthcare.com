<?php

use App\Models\Blog;
use App\Models\BusinessProfile;
use App\Models\Review;
use App\Models\Service;
use App\Models\SeoTechnical;
use App\Models\User;
use App\Services\Growth\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('includes lastmod in sitemap index entries', function () {
    config(['sitemap.paginated_enabled' => true, 'sitemap.cache_enabled' => false]);

    $index = app(SeoService::class)->generateSitemapIndex();

    expect($index)->toContain('<lastmod>');
});

it('outputs bing verification meta when configured', function () {
    if (! Schema::hasTable('seo_technical') || ! Schema::hasTable('business_profiles')) {
        $this->markTestSkipped('SEO technical tables missing.');
    }

    $profile = BusinessProfile::query()->create([
        'name' => 'Medca Health Care',
        'website' => config('app.url'),
        'email' => 'hello@example.com',
    ]);

    SeoTechnical::query()->create([
        'business_profile_id' => $profile->id,
        'robots_txt' => 'User-agent: *',
        'sitemap_enabled' => true,
        'canonical_url' => config('app.url'),
        'indexable' => true,
        'bing_site_verification' => 'bing-verify-token',
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('name="msvalidate.01"', false)
        ->assertSee('bing-verify-token', false);
});

it('renders blog posting json-ld on blog pages', function () {
    if (! Schema::hasTable('blogs')) {
        $this->markTestSkipped('Blogs table missing.');
    }

    $blog = Blog::query()->create([
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'title' => 'Home Care Tips',
        'slug' => 'home-care-tips',
        'content' => 'Sample content',
        'is_published' => true,
        'published_at' => now()->subDay(),
        'meta_title' => 'Home Care Tips',
    ]);

    $this->get('/blog/'.$blog->slug)
        ->assertSuccessful()
        ->assertSee('BlogPosting', false)
        ->assertSee('article:published_time', false);
});

it('includes aggregate rating in service schema when reviews exist', function () {
    $service = Service::factory()->create(['is_active' => true]);
    $user = User::factory()->create();

    Review::query()->create([
        'user_id' => $user->id,
        'service_id' => $service->id,
        'rating' => 5,
        'comment' => 'Excellent care',
        'status' => Review::STATUS_APPROVED,
    ]);

    $schema = $service->fresh()->toServiceSchema();

    expect($schema)->toHaveKey('aggregateRating')
        ->and($schema['aggregateRating']['@type'])->toBe('AggregateRating')
        ->and($schema['aggregateRating']['reviewCount'])->toBe(1);
});

it('accepts core web vitals rum beacons', function () {
    config(['growth.rum.enabled' => true]);

    $this->postJson('/api/v1/performance/rum', [
        'name' => 'LCP',
        'value' => 1800,
        'rating' => 'good',
        'path' => '/',
    ])->assertSuccessful()
        ->assertJson(['accepted' => true]);
});

it('resolves orphan seo technical rows for ai-discovery', function () {
    if (! Schema::hasTable('seo_technical') || ! Schema::hasTable('business_profiles')) {
        $this->markTestSkipped('Growth Center global tables are not migrated.');
    }

    $profile = BusinessProfile::query()->create([
        'name' => 'Medca Health Care',
        'website' => config('app.url'),
        'email' => 'hello@example.com',
    ]);

    SeoTechnical::query()->create([
        'business_profile_id' => null,
        'robots_txt' => null,
        'sitemap_enabled' => true,
        'canonical_url' => config('app.url'),
        'indexable' => true,
        'ai_discovery_enabled' => true,
    ]);

    $this->getJson('/ai-discovery')
        ->assertSuccessful()
        ->assertJsonStructure(['services', 'pages', 'locations', 'business', 'contact']);
});

it('serves dynamic robots.txt with sitemap reference', function () {
    $this->get('/robots.txt')
        ->assertSuccessful()
        ->assertSeeText('Sitemap:')
        ->assertSeeText('sitemap.xml');
});

it('serves indexnow key file when configured', function () {
    config(['growth.indexnow.key' => 'abc12345deadbeef']);

    $this->get('/abc12345deadbeef.txt')
        ->assertSuccessful()
        ->assertSeeText('abc12345deadbeef');
});
