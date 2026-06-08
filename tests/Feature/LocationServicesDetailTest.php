<?php

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceFaq;
use App\Services\Public\PublicPagePresenter;

it('loads all localized services for the locations page payload', function () {
    $pin = PinCode::factory()->create(['pincode' => '560078', 'is_active' => true]);

    $services = Service::factory()->count(8)->create();
    foreach ($services as $service) {
        $service->pincodes()->attach($pin->id);
    }

    session(['medca.detected_pincode' => '560078']);

    $payload = app(PublicPagePresenter::class)->nearYouPayload(limit: 0);

    expect($payload['services'])->toHaveCount(8);
});

it('renders detailed service content on the locations near-you partial', function () {
    $pin = PinCode::factory()->create([
        'pincode' => '560078',
        'area_name' => 'JP Nagar',
        'is_active' => true,
    ]);

    $service = Service::factory()->create([
        'title' => 'Home Nursing Care',
        'short_summary' => 'Doctor-supervised nursing at home.',
        'description' => '<p>Comprehensive bedside nursing with vitals monitoring.</p>',
    ]);
    $service->pincodes()->attach($pin->id);
    ServiceFaq::factory()->create([
        'service_id' => $service->id,
        'question' => 'How quickly can a nurse arrive?',
        'answer' => 'Usually within 2 hours in JP Nagar.',
    ]);

    $html = view('public.partials.near-you-services', [
        'services' => collect([$service->load(['categories', 'faqs'])]),
        'pincode' => '560078',
        'pinCodeRecord' => $pin,
        'locationRequired' => false,
        'contentSlug' => 'near-you-locations',
        'blockSettings' => [],
    ])->render();

    expect($html)
        ->toContain('data-section="near-you"')
        ->not->toContain('medca-hero-gradient')
        ->toContain('data-location-services-detail')
        ->toContain('Home Nursing Care')
        ->toContain('Comprehensive bedside nursing')
        ->toContain('How quickly can a nurse arrive?');
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
