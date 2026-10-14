<?php

use App\Http\Middleware\ApplyUrlRedirects;
use App\Models\MarketingAttributionSession;
use App\Models\MarketingSetting;
use App\Models\UrlRedirect;
use App\Services\Marketing\Attribution\UtmCaptureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('maps gclid-only landings to google cpc for channel attribution', function () {
    $this->withoutVite()
        ->get('/?gclid=Cj0KCQjw-test')
        ->assertSuccessful();

    $payload = app(UtmCaptureService::class)->extractUtmPayload(request());

    expect($payload['gclid'])->toBe('Cj0KCQjw-test')
        ->and($payload['utm_source'])->toBe('google')
        ->and($payload['utm_medium'])->toBe('cpc');

    $session = MarketingAttributionSession::query()->first();
    expect($session)->not->toBeNull()
        ->and($session->utm_source)->toBe('google')
        ->and($session->utm_medium)->toBe('cpc')
        ->and($session->gclid)->toBe('Cj0KCQjw-test');
});

it('does not override explicit utms when gclid is present', function () {
    $request = Request::create('/', 'GET', [
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'brand',
        'gclid' => 'should-not-override',
    ]);

    $payload = app(UtmCaptureService::class)->extractUtmPayload($request);

    expect($payload['utm_source'])->toBe('bing')
        ->and($payload['utm_medium'])->toBe('cpc')
        ->and($payload['utm_campaign'])->toBe('brand')
        ->and($payload['gclid'])->toBe('should-not-override');
});

it('preserves gclid and utm query params across url redirects', function () {
    if (! Schema::hasTable('url_redirects')) {
        $this->markTestSkipped('url_redirects table missing.');
    }

    UrlRedirect::query()->create([
        'from_path' => '/ads-landing',
        'to_url' => '/contact',
        'status_code' => 301,
        'is_active' => true,
    ]);

    $request = Request::create(
        '/ads-landing?gclid=abc123&utm_source=google&utm_medium=cpc&utm_campaign=home_care',
        'GET'
    );
    $middleware = app(ApplyUrlRedirects::class);
    $response = $middleware->handle($request, fn () => response('ok', 200));

    $location = (string) $response->headers->get('Location');

    expect($response->getStatusCode())->toBe(301)
        ->and($location)->toContain('/contact')
        ->and($location)->toContain('gclid=abc123')
        ->and($location)->toContain('utm_source=google')
        ->and($location)->toContain('utm_medium=cpc')
        ->and($location)->toContain('utm_campaign=home_care');
});

it('enables ga4 url passthrough and always renders utm form fields', function () {
    MarketingSetting::query()->updateOrCreate(
        ['id' => 1],
        ['ga4_measurement_id' => 'G-PAIDSEARCHTEST']
    );

    $home = $this->withoutVite()->get('/')->assertSuccessful()->getContent();
    expect($home)->toContain("gtag('set', 'url_passthrough', true)");

    $form = view('components.public.lead-capture-form')->render();
    expect($form)
        ->toContain('data-medca-utm-field="utm_source"')
        ->toContain('data-medca-utm-field="utm_medium"')
        ->toContain('data-medca-utm-field="gclid"');
});
