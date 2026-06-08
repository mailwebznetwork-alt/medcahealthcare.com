<?php

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use App\Services\Operations\ServiceDetailPageProvisioner;
use App\Services\Operations\ServiceLocationPageProvisioner;
use App\Services\Seo\UnifiedJsonLdGraphBuilder;
use Database\Seeders\MedcaPublicPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['medca.hide_visual_breadcrumbs' => true]);
});

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

    $mapping->page?->update([
        'schema_json' => app(UnifiedJsonLdGraphBuilder::class)->buildLocationGraph(
            $service->fresh(['seo', 'faqs']),
            $pin,
            $mapping
        ),
    ]);

    $html = $this->get($mapping->publicUrl())
        ->assertSuccessful()
        ->getContent();

    expect($html)
        ->toContain('BreadcrumbList')
        ->not->toContain('aria-label="Breadcrumb"');
});

it('hides visible breadcrumbs on category fallback pages', function () {
    $category = ServiceCategory::factory()->create([
        'code' => 'home-care',
        'name' => 'Home Care',
        'is_active' => true,
        'page_id' => null,
    ]);

    $html = $this->get(route('public.service-categories.show', $category->code))
        ->assertSuccessful()
        ->getContent();

    expect($html)->not->toContain('aria-label="Breadcrumb"');
});

it('hides visible breadcrumbs on location area pages', function () {
    $pin = PinCode::factory()->create([
        'pincode' => '560076',
        'area_name' => 'Bannerghatta',
        'is_active' => true,
    ]);

    $slug = app(\App\Services\Public\PinCodeAreaResolver::class)->routeSlugFor($pin);

    $html = $this->get(route('public.locations.area', ['slug' => $slug]))
        ->assertSuccessful()
        ->getContent();

    expect($html)->not->toContain('aria-label="Breadcrumb"');
});

it('hides visible breadcrumbs on the home page', function () {
    $this->seed(MedcaPublicPagesSeeder::class);

    $html = $this->get('/')
        ->assertSuccessful()
        ->getContent();

    expect($html)->not->toContain('aria-label="Breadcrumb"');
});

it('can re-enable visual breadcrumbs via feature flag', function () {
    config(['medca.hide_visual_breadcrumbs' => false]);

    $pin = PinCode::factory()->create([
        'pincode' => '560078',
        'area_name' => 'JP Nagar',
        'is_active' => true,
    ]);

    $slug = app(\App\Services\Public\PinCodeAreaResolver::class)->routeSlugFor($pin);

    $html = $this->get(route('public.locations.area', ['slug' => $slug]))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('aria-label="Breadcrumb"');
});

it('hides visible breadcrumbs on sub-service pages while keeping breadcrumb schema', function () {
    $service = Service::factory()->create([
        'service_code' => 'medical-lab',
        'title' => 'Medical Lab',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'blood-test',
        'title' => 'Blood Test',
        'is_active' => true,
        'publish_status' => 'published',
        'visibility' => 'public',
    ]);

    $page = app(\App\Services\Operations\SubServicePageProvisioner::class)->syncFromSubService($sub);
    $page->update([
        'schema_json' => app(\App\Services\Seo\SubServiceJsonLdBuilder::class)->buildGraph($sub->fresh(['seo', 'faqs', 'service'])),
    ]);

    $html = $this->get(route('public.services.sub', ['code' => 'medical-lab', 'subCode' => 'blood-test']))
        ->assertSuccessful()
        ->getContent();

    expect($html)
        ->toContain('BreadcrumbList')
        ->not->toContain('aria-label="Breadcrumb"');
});
