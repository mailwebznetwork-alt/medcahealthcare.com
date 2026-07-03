<?php

use App\Models\UrlRedirect;
use App\Services\Growth\SeoDiscoveryBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('bootstraps seo discovery phase a settings', function () {
    if (! Schema::hasTable('seo_technical') || ! Schema::hasTable('business_profiles')) {
        $this->markTestSkipped('SEO tables missing.');
    }

    $result = app(SeoDiscoveryBootstrapService::class)->run(pingSearchEngines: false);

    expect($result['ai_discovery_enabled'])->toBeTrue()
        ->and($result['indexnow_key'])->not->toBeNull()
        ->and(strlen((string) $result['indexnow_key']))->toBeGreaterThan(8);
});

it('applies url redirect middleware', function () {
    if (! Schema::hasTable('url_redirects')) {
        $this->markTestSkipped('url_redirects table missing.');
    }

    UrlRedirect::query()->create([
        'from_path' => '/legacy-promo',
        'to_url' => '/services-catalog',
        'status_code' => 301,
        'is_active' => true,
    ]);

    $request = \Illuminate\Http\Request::create('/legacy-promo', 'GET');
    $middleware = app(\App\Http\Middleware\ApplyUrlRedirects::class);
    $response = $middleware->handle($request, fn () => response('ok', 200));

    expect($response->getStatusCode())->toBe(301)
        ->and($response->headers->get('Location'))->toContain('services-catalog');
});

it('renders searchaction schema on homepage', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('SearchAction', false)
        ->assertSee('WebSite', false);
});
