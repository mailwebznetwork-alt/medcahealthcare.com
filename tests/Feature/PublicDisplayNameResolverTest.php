<?php

use App\Enums\PageCategory;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Services\Operations\ServiceLocationPageProvisioner;
use App\Services\Public\PublicDisplayNameResolver;

it('prefers live service title over stale cms page title for location pages', function () {
    $pin = PinCode::factory()->create([
        'pincode' => '560083',
        'area_name' => 'Bannerghatta Road',
        'is_active' => true,
    ]);

    $service = Service::factory()->create([
        'service_code' => 'SRV-LAB-1',
        'title' => 'Blood Tests at Home',
    ]);
    $service->pincodes()->attach($pin->id);

    $page = Page::factory()->create([
        'title' => 'Medical Lab in Bannerghatta Road',
        'meta_title' => 'Medical Lab Tests at Home Bangalore | Medca — Bannerghatta Road',
        'page_category' => PageCategory::Location,
        'page_source' => 'generated',
        'registry_owner' => 'operations_location_matrix',
    ]);

    $mapping = ServiceLocationPage::query()->create([
        'service_id' => $service->id,
        'pincode_id' => $pin->id,
        'page_id' => $page->id,
        'slug' => 'service-SRV-LAB-1-loc-560083',
        'location_slug' => 'bannerghatta-road',
        'city_slug' => 'bangalore',
    ]);

    $mapping->load(['service', 'pincode', 'page']);

    $resolver = app(PublicDisplayNameResolver::class);
    $meta = $resolver->documentMeta($page, $service, null, $mapping);

    expect($meta['title'])->toBe('Blood Tests at Home in Bannerghatta Road')
        ->and($meta['meta_title'])->toContain('Blood Tests at Home')
        ->and($meta['prefer_live_schema'])->toBeTrue();
});

it('strips legacy top-rated prefix from service headlines', function () {
    $service = Service::factory()->create([
        'service_code' => 'SRV-EQUIP-1',
        'title' => 'Hospital Care Equipment',
    ]);

    $service->seo()->updateOrCreate(
        ['service_id' => $service->id],
        [
            'h1' => 'Top-Rated Hospital Care Equipment Services',
            'meta_title' => 'Hospital Care Equipment',
        ],
    );

    expect(app(PublicDisplayNameResolver::class)->serviceHeadline($service->fresh()))
        ->toBe('Hospital Care Equipment Services');
});

it('uses live category name for category document meta', function () {
    $category = ServiceCategory::factory()->create([
        'code' => 'cat-lab',
        'name' => 'Medical Lab Services',
    ]);

    $page = Page::factory()->create([
        'title' => 'Medical Lab',
        'meta_title' => 'Medical Lab',
        'page_source' => 'generated',
        'registry_owner' => 'operations_category',
    ]);

    $meta = app(PublicDisplayNameResolver::class)->documentMeta($page, null, $category);

    expect($meta['title'])->toBe('Medical Lab Services')
        ->and($meta['meta_title'])->toBe('Medical Lab Services');
});

it('falls back to ai summary for service card text', function () {
    $service = Service::factory()->create([
        'service_code' => 'SRV-CARE-1',
        'title' => 'Alzheimer\'s Care',
        'short_summary' => null,
        'ai_summary' => 'Compassionate in-home Alzheimer\'s care with trained caregivers in Bangalore.',
    ]);

    expect(app(PublicDisplayNameResolver::class)->serviceCardSummary($service->fresh()))
        ->toBe('Compassionate in-home Alzheimer\'s care with trained caregivers in Bangalore.');
});

it('falls back to description for category card text', function () {
    $category = ServiceCategory::factory()->create([
        'code' => 'cat-caregiver-services',
        'name' => 'Caregiver Services',
        'short_summary' => null,
        'description' => '<p>Professional caregivers for seniors and patients at home.</p>',
    ]);

    expect(app(PublicDisplayNameResolver::class)->categoryCardSummary($category->fresh()))
        ->toBe('Professional caregivers for seniors and patients at home.');
});

it('renders location breadcrumbs from live database labels', function () {
    $pin = PinCode::factory()->create([
        'pincode' => '560083',
        'area_name' => 'Bannerghatta Road',
        'is_active' => true,
    ]);

    $service = Service::factory()->create([
        'service_code' => 'SRV-LAB-1',
        'title' => 'Blood Tests at Home',
    ]);
    $service->pincodes()->attach($pin->id);

    $provisioner = app(ServiceLocationPageProvisioner::class);
    $page = $provisioner->provisionOne($service, $pin);

    $mapping = ServiceLocationPage::query()
        ->where('service_id', $service->id)
        ->where('pincode_id', $pin->id)
        ->firstOrFail();

    $page->forceFill([
        'title' => 'Medical Lab in Bannerghatta Road',
        'meta_title' => 'Legacy Medical Lab Title',
    ])->saveQuietly();

    $response = $this->get(route('public.services.location', [
        'code' => $service->service_code,
        'locationSlug' => $mapping->location_slug,
    ]));

    $response->assertOk();
    expect($response->getContent())
        ->toContain('Blood Tests at Home in Bannerghatta Road')
        ->not->toContain('Medical Lab in Bannerghatta Road');
});
