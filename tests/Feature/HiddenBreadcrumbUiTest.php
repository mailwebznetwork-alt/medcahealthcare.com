<?php

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Operations\ServiceDetailPageProvisioner;
use App\Services\Operations\ServiceLocationPageProvisioner;
use App\Services\Seo\UnifiedJsonLdGraphBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('hides visible breadcrumbs on service detail pages while keeping breadcrumb schema', function () {
    $service = Service::factory()->create([
        'service_code' => 'home-nursing',
        'title' => 'Home Nursing',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $page = app(ServiceDetailPageProvisioner::class)->provision($service);
    $page->update([
        'schema_json' => app(UnifiedJsonLdGraphBuilder::class)->buildServiceGraph($service->fresh(['seo', 'faqs'])),
    ]);

    $html = $this->get('/services/home-nursing')
        ->assertSuccessful()
        ->getContent();

    expect($html)
        ->toContain('BreadcrumbList')
        ->not->toContain('aria-label="Breadcrumb"');
});

it('hides visible breadcrumbs on service location pages while keeping breadcrumb schema', function () {
    $pin = PinCode::factory()->create([
        'pincode' => '560078',
        'area_name' => 'JP Nagar',
        'is_active' => true,
    ]);

    $service = Service::factory()->create([
        'service_code' => 'medical-lab',
        'title' => 'Medical Lab',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);
    $service->pincodes()->attach($pin->id);

    app(ServiceLocationPageProvisioner::class)->provisionOne($service, $pin);

    $mapping = ServiceLocationPage::query()
        ->where('service_id', $service->id)
        ->where('pincode_id', $pin->id)
        ->firstOrFail();

    $html = $this->get($mapping->publicUrl())
        ->assertSuccessful()
        ->getContent();

    expect($html)
        ->toContain('BreadcrumbList')
        ->not->toContain('aria-label="Breadcrumb"');
});
