<?php

use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Operations\ServiceMasterOrchestrator;

it('redirects legacy cms service page slug to public service url', function () {
    $service = Service::factory()->create(['service_code' => 'home-nursing', 'title' => 'Home Nursing']);
    app(ServiceMasterOrchestrator::class)->sync($service->fresh());

    $response = $this->get('/p/service-home-nursing');

    $response->assertRedirect('/services/home-nursing');
});

it('renders public location url', function () {
    $service = Service::factory()->create(['service_code' => 'care-test', 'title' => 'Care Test']);
    $pin = PinCode::factory()->create(['pincode' => '560076', 'area_name' => 'Arekere', 'is_active' => true]);
    $service->pincodes()->attach($pin->id);

    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes']));

    $mapping = ServiceLocationPage::query()->where('service_id', $service->id)->first();
    expect($mapping)->not->toBeNull();

    $response = $this->get('/services/care-test/'.$mapping->location_slug);

    $response->assertOk();
});

it('exposes html sitemap page', function () {
    $this->get('/sitemap')->assertOk();
});
