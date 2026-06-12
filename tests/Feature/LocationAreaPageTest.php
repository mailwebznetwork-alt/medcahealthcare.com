<?php

use App\Models\PinCode;
use App\Models\PinCodeLocationFaq;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders geo location area page with unified layout sections', function () {
    $current = PinCode::factory()->create([
        'pincode' => '560901',
        'area_name' => 'JP Nagar',
        'city' => 'Bangalore',
        'is_active' => true,
        'is_serviceable' => true,
        'geo_page_ready' => true,
        'coverage_text' => 'Coverage across JP Nagar and surrounding blocks.',
    ]);

    $other = PinCode::factory()->create([
        'pincode' => '560902',
        'area_name' => 'Arekere',
        'city' => 'Bangalore',
        'is_active' => true,
        'is_serviceable' => true,
        'geo_page_ready' => true,
    ]);

    $service = Service::factory()->create([
        'title' => 'Home Nursing Care',
        'description' => '<p>Doctor-supervised nursing at home.</p>',
    ]);
    $service->pincodes()->attach($current->id);

    PinCodeLocationFaq::query()->create([
        'pincode_id' => $current->id,
        'question' => 'Do you cover JP Nagar?',
        'answer' => 'Yes, same-day visits are available.',
        'sort_order' => 0,
    ]);

    $response = $this->get('/locations/jp-nagar');

    $response->assertOk()
        ->assertSee('Healthcare Services in JP Nagar', false)
        ->assertSee('Change Pincode', false)
        ->assertSee('Home Nursing Care', false)
        ->assertSee('Areas We Serve', false)
        ->assertSee('Arekere', false)
        ->assertDontSee('About JP Nagar healthcare coverage', false)
        ->assertSee('Local FAQ', false)
        ->assertSee('Do you cover JP Nagar?', false)
        ->assertDontSee('Book care in your neighbourhood', false);
});

it('redirects non-canonical location slugs to the area route slug', function () {
    PinCode::factory()->create([
        'pincode' => '560903',
        'area_name' => 'Zzuniqarea903',
        'slug' => 'zzuniqarea903-560903',
        'is_active' => true,
        'geo_page_ready' => true,
    ]);

    $this->get('/locations/zzuniqarea903-560903')
        ->assertRedirect('/locations/zzuniqarea903');
});

it('resolves coverage urls to geo location pages when geo_page_ready', function () {
    $pin = PinCode::factory()->create([
        'pincode' => '560904',
        'area_name' => 'JP Nagar',
        'is_active' => true,
        'geo_page_ready' => true,
    ]);

    $url = app(\App\Services\Public\PinCodeCoverageUrlResolver::class)->urlFor($pin);

    expect($url)->toBe(route('public.locations.area', ['slug' => 'jp-nagar']));
});

it('renders service location detail page with shared areas we cover component', function () {
    $current = PinCode::factory()->create([
        'pincode' => '560905',
        'area_name' => 'JP Nagar',
        'is_active' => true,
    ]);
    $other = PinCode::factory()->create([
        'pincode' => '560906',
        'area_name' => 'Arekere',
        'is_active' => true,
        'geo_page_ready' => true,
    ]);

    $service = Service::factory()->create([
        'title' => 'Physiotherapy at Home',
        'description' => '<p>Personalized rehab plans in your neighbourhood.</p>',
    ]);
    $service->pincodes()->attach([$current->id, $other->id]);

    $mapping = new ServiceLocationPage([
        'service_id' => $service->id,
        'pincode_id' => $current->id,
        'location_slug' => 'jp-nagar',
    ]);
    $mapping->setRelation('pincode', $current);
    $mapping->setRelation('service', $service);

    $html = view('blocks.locations.location-geo-enrichment', [
        'service' => $service,
        'serviceLocation' => $mapping,
    ])->render();

    expect($html)
        ->toContain('data-page-hero="location"')
        ->toContain('Areas We Serve')
        ->not->toContain('About JP Nagar healthcare coverage')
        ->not->toContain('Book care in your neighbourhood')
        ->not->toContain('Areas served');
});
