<?php

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Public\PublicPagePresenter;

it('loads all localized categories for the locations page payload', function () {
    $pin = PinCode::factory()->create(['pincode' => '560078', 'is_active' => true]);
    $category = ServiceCategory::factory()->create(['code' => 'near-you-cat', 'name' => 'Near You Cat']);

    $services = Service::factory()->count(8)->create();
    foreach ($services as $service) {
        $service->pincodes()->attach($pin->id);
        $service->categories()->attach($category->id);
    }

    session(['medca.detected_pincode' => '560078']);

    $payload = app(PublicPagePresenter::class)->nearYouPayload(limit: 0);

    expect($payload['categories'])->toHaveCount(1);
});

it('renders the home-style category grid on the locations near-you partial', function () {
    $pin = PinCode::factory()->create([
        'pincode' => '560078',
        'area_name' => 'JP Nagar',
        'is_active' => true,
    ]);

    $category = ServiceCategory::factory()->create([
        'code' => 'home-nursing',
        'name' => 'Home Nursing Services',
        'description' => 'Doctor-supervised nursing at home.',
    ]);

    $service = Service::factory()->create(['title' => 'Home Nursing Care']);
    $service->pincodes()->attach($pin->id);
    $service->categories()->attach($category->id);

    $category->load(['services' => fn ($q) => $q->publicListing()->forPincode('560078')]);

    $html = view('public.partials.near-you-services', [
        'categories' => collect([$category]),
        'pincode' => '560078',
        'pinCodeRecord' => $pin,
        'locationRequired' => false,
        'contentSlug' => 'near-you-locations',
        'blockSettings' => [],
    ])->render();

    expect($html)
        ->toContain('data-section="near-you"')
        ->not->toContain('medca-hero-gradient')
        ->not->toContain('data-location-services-detail')
        ->toContain('Home Nursing Services')
        ->toContain('Doctor-supervised nursing at home.')
        ->toContain('View category')
        ->toContain('Care category')
        ->toContain('catalog-list-card__image')
        ->not->toContain('Home Nursing Care')
        ->not->toContain('1 service');
});

it('renders detailed service content on service location geo enrichment', function () {
    $pin = PinCode::factory()->create([
        'pincode' => '560907',
        'area_name' => 'JP Nagar',
        'is_active' => true,
    ]);

    $service = Service::factory()->create([
        'title' => 'Physiotherapy at Home',
        'description' => '<p>Personalized rehab plans in your neighbourhood.</p>',
    ]);
    $service->pincodes()->attach($pin->id);

    $mapping = new \App\Models\ServiceLocationPage([
        'service_id' => $service->id,
        'pincode_id' => $pin->id,
        'location_slug' => 'jp-nagar',
    ]);
    $mapping->setRelation('pincode', $pin);
    $mapping->setRelation('service', $service);

    $html = view('blocks.locations.location-geo-enrichment', [
        'service' => $service,
        'serviceLocation' => $mapping,
    ])->render();

    expect($html)
        ->toContain('data-page-hero="location"')
        ->toContain('medca-hero-gradient')
        ->toContain('data-location-service="')
        ->toContain('Physiotherapy at Home')
        ->toContain('Personalized rehab plans');
});

it('renders the global service page hero layout', function () {
    $service = Service::factory()->create([
        'title' => 'Medical Lab',
        'short_summary' => 'Home sample collection in Bangalore.',
        'description' => '<p>Certified partner labs with home collection.</p>',
    ]);
    $service->pincodes()->attach(PinCode::factory()->create(['is_active' => true])->id);

    $html = view('components.public.service-page-hero', ['service' => $service, 'tone' => 'brand'])->render();

    expect($html)
        ->toContain('data-page-hero="service"')
        ->toContain('Medical Lab')
        ->toContain('Home sample collection')
        ->toContain('WhatsApp Us')
        ->not->toContain('Certified partner labs');
});
