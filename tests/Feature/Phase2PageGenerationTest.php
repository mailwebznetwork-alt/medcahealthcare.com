<?php

use App\Models\BusinessProfile;
use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Discovery\ChangePincodeEngine;
use App\Services\Discovery\FeaturedContentEngine;
use App\Services\Discovery\HealthcareDiscoveryEngine;
use App\Services\Discovery\RelatedContentEngine;
use App\Services\Operations\CategoryPageProvisioner;
use App\Services\Operations\ServiceGeneratedPageEligibility;
use App\Services\Operations\SubServicePageProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates category discovery page from database', function () {
    BusinessProfile::query()->create([
        'name' => 'Medca',
        'city' => 'Mysuru',
        'website' => config('app.url'),
    ]);

    $category = ServiceCategory::query()->create([
        'name' => 'Medical Lab',
        'code' => 'medical-lab',
        'description' => 'Lab diagnostics at home.',
        'is_active' => true,
        'is_featured' => true,
    ]);

    $page = app(CategoryPageProvisioner::class)->syncFromCategory($category->fresh());

    expect($page)->toBeInstanceOf(Page::class)
        ->and($page->page_category?->value)->toBe('category')
        ->and($page->page_source)->toBe('generated')
        ->and($category->fresh()->page_id)->toBe($page->id)
        ->and($page->schema_json)->toHaveKey('@graph');
});

it('generates sub service page and registers in page registry', function () {
    $service = Service::factory()->create([
        'service_code' => 'med-lab',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'blood-test',
        'title' => 'Blood Test',
        'description' => 'Complete blood count at home.',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $page = app(SubServicePageProvisioner::class)->syncFromSubService($sub->fresh());

    app(\App\Services\Governance\UniversalPageRegistry::class)->syncAll();

    expect($page->page_category?->value)->toBe('sub_service')
        ->and($sub->fresh()->page_id)->toBe($page->id)
        ->and(PageRegistry::query()->where('entity_type', 'sub_service')->exists())->toBeTrue();
});

it('generates sub-service pages without parent service pincode coverage', function () {
    $service = Service::factory()->create([
        'service_code' => 'no-geo-parent',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'standalone-sub',
        'title' => 'Standalone Sub',
        'description' => 'Available without GEO matrix.',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $page = app(SubServicePageProvisioner::class)->syncFromSubService($sub->fresh());

    expect($page->page_category?->value)->toBe('sub_service')
        ->and($sub->fresh()->page_id)->toBe($page->id)
        ->and(ServiceGeneratedPageEligibility::subServiceMayHavePages($sub))->toBeTrue()
        ->and(ServiceGeneratedPageEligibility::serviceMayHavePages($service))->toBeFalse();
});

it('relinks an orphan sub-service page without running full sync on every request', function () {
    $service = Service::factory()->create([
        'service_code' => 'parent-for-sub',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $sub = SubService::withoutEvents(function () use ($service) {
        return SubService::query()->create([
            'service_id' => $service->id,
            'sub_service_code' => 'orphan-sub',
            'title' => 'Orphan Sub',
            'publish_status' => 'published',
            'visibility' => 'public',
            'is_active' => true,
        ]);
    });

    $page = Page::query()->create([
        'title' => $sub->title,
        'slug' => 'service-parent-for-sub-sub-orphan-sub',
        'content' => (string) config('phase2_discovery.sub_service_page_content'),
        'is_active' => true,
        'page_source' => 'generated',
        'page_category' => 'sub_service',
    ]);

    expect($sub->fresh()->page_id)->toBeNull();

    $relinked = app(SubServicePageProvisioner::class)->relinkOwnedPage($sub->fresh());

    expect($relinked?->id)->toBe($page->id)
        ->and($sub->fresh()->page_id)->toBe($page->id);
});

it('serves sub-service URLs with uppercase catalog codes', function () {
    $service = Service::factory()->create([
        'service_code' => 'SRV-elderly-care',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $sub = SubService::withoutEvents(function () use ($service) {
        return SubService::query()->create([
            'service_id' => $service->id,
            'sub_service_code' => 'SUB-elderly-care-feeding-assistance',
            'title' => 'Feeding Assistance',
            'publish_status' => 'published',
            'visibility' => 'public',
            'is_active' => true,
        ]);
    });

    app(SubServicePageProvisioner::class)->syncFromSubService($sub->fresh(['service']));

    $this->get('/services/SRV-elderly-care/sub/SUB-elderly-care-feeding-assistance')
        ->assertSuccessful()
        ->assertSee('Feeding Assistance', false);
});

it('discovers category service hierarchy from database', function () {
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
        'is_featured' => true,
    ]);
    $category->services()->attach($service->id);

    $discovered = app(HealthcareDiscoveryEngine::class)->discoverServices($category->id);

    expect($discovered)->toHaveCount(1)
        ->and($discovered->first()->service_code)->toBe($service->service_code);
});

it('builds related content links dynamically', function () {
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);
    $category->services()->attach($service->id);

    $links = app(RelatedContentEngine::class)->buildForCategory($category);

    expect($links['related_services'])->not->toBeEmpty();
});

it('switches pincode and returns discovery payload', function () {
    $pin = \App\Models\PinCode::factory()->create([
        'pincode' => '570001',
        'is_active' => true,
        'is_serviceable' => true,
    ]);
    $service = Service::factory()->create([
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);
    $service->pincodes()->attach($pin->id, ['is_visible' => true]);

    $result = app(ChangePincodeEngine::class)->switch('570001');

    expect($result['success'])->toBeTrue()
        ->and($result['pincode'])->toBe('570001')
        ->and($result['discovery'])->toHaveKey('services');
});

it('surfaces featured services from database flags', function () {
    Service::factory()->create([
        'is_featured' => true,
        'show_on_homepage' => true,
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $featured = app(FeaturedContentEngine::class)->featuredServices('homepage');

    expect($featured)->toHaveCount(1);
});

it('relinks an orphan category page without running full sync on every request', function () {
    $category = ServiceCategory::query()->create([
        'name' => 'Home Nursing',
        'code' => 'cat-home-nursing-services',
        'is_active' => true,
    ]);

    $page = Page::query()->create([
        'title' => $category->name,
        'slug' => 'category-cat-home-nursing-services',
        'content' => (string) config('phase2_discovery.category_page_content'),
        'is_active' => true,
        'page_source' => 'generated',
        'page_category' => 'category',
    ]);

    expect($category->fresh()->page_id)->toBeNull();

    $relinked = app(CategoryPageProvisioner::class)->relinkOwnedPage($category->fresh());

    expect($relinked?->id)->toBe($page->id)
        ->and($category->fresh()->page_id)->toBe($page->id);

    $this->get(route('public.service-categories.show', $category->code))
        ->assertSuccessful()
        ->assertSee('Home Nursing', false);
});
