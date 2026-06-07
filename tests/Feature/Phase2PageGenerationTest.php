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
