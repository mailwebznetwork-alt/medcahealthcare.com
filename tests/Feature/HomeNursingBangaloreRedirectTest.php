<?php

use App\Http\Middleware\ApplyUrlRedirects;
use App\Models\UrlRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('seeds the google ads home-nursing bangalore redirect to services', function () {
    if (! Schema::hasTable('url_redirects')) {
        $this->markTestSkipped('url_redirects table missing.');
    }

    $redirect = UrlRedirect::query()
        ->where('from_path', '/home-nursing/bangalore')
        ->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->to_url)->toBe('https://medcahealthcare.com/services')
        ->and($redirect->status_code)->toBe(301)
        ->and($redirect->is_active)->toBeTrue();
});

it('redirects home-nursing bangalore to services and keeps ad query params', function () {
    if (! Schema::hasTable('url_redirects')) {
        $this->markTestSkipped('url_redirects table missing.');
    }

    $request = Request::create(
        '/home-nursing/bangalore?utm_source=google&utm_medium=cpc&utm_campaign=nursing_bangalore&gclid=ads-test-1',
        'GET'
    );

    $response = app(ApplyUrlRedirects::class)->handle($request, fn () => response('ok', 200));
    $location = (string) $response->headers->get('Location');

    expect($response->getStatusCode())->toBe(301)
        ->and($location)->toStartWith('https://medcahealthcare.com/services')
        ->and($location)->toContain('utm_source=google')
        ->and($location)->toContain('utm_medium=cpc')
        ->and($location)->toContain('utm_campaign=nursing_bangalore')
        ->and($location)->toContain('gclid=ads-test-1');
});
