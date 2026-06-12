<?php

use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Blocks\BlockTemplateSyncService;
use App\Services\Public\PinCodeCoverageUrlResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders locations coverage grid with search sort and expandable cards', function () {
    app(BlockTemplateSyncService::class)->sync(categories: ['locations']);

    PinCode::factory()->create([
        'pincode' => '560076',
        'area_name' => 'Arekere',
        'city' => 'Bangalore',
        'is_active' => true,
        'is_serviceable' => true,
    ]);
    PinCode::factory()->create([
        'pincode' => '560078',
        'area_name' => 'JP Nagar',
        'city' => 'Bangalore',
        'is_active' => true,
        'is_serviceable' => true,
    ]);

    Page::query()->create([
        'slug' => 'locations',
        'title' => 'Locations',
        'content' => '{{block:locations-coverage}}',
        'is_active' => true,
    ]);

    $response = $this->get('/locations');

    $response->assertOk()
        ->assertSee('Areas We Serve', false)
        ->assertSee('Search pincode or area', false)
        ->assertSee('Arekere', false)
        ->assertSee('JP Nagar', false)
        ->assertSee('medcaLocationsCoverage', false)
        ->assertSee('View more areas', false);
});

it('resolves coverage urls to service location pages when available', function () {
    $pin = PinCode::factory()->create([
        'pincode' => '560076',
        'area_name' => 'Arekere',
        'city' => 'Bangalore',
        'is_active' => true,
    ]);
    $service = Service::factory()->create([
        'service_code' => 'elder-care',
    ]);
    $page = Page::factory()->create(['is_active' => true]);
    $mapping = ServiceLocationPage::query()->create([
        'service_id' => $service->id,
        'pincode_id' => $pin->id,
        'page_id' => $page->id,
        'slug' => 'service-elder-care-loc-560076',
        'location_slug' => 'elder-care-in-arekere',
        'is_indexable' => true,
    ]);

    $url = app(PinCodeCoverageUrlResolver::class)->urlFor($pin);

    expect($url)->toBe($mapping->publicUrl());
});

it('selects a pincode and redirects to the locations near-you section', function () {
    PinCode::factory()->create([
        'pincode' => '560076',
        'area_name' => 'Arekere',
        'is_active' => true,
        'is_serviceable' => true,
    ]);

    $response = $this->get('/location/pincode/560076');

    $response->assertRedirect(url('/locations').'#near-you');
    expect(session('medca.detected_pincode'))->toBe('560076');
});
