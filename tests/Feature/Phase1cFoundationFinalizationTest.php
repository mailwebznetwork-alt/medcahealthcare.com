<?php

use App\Models\BusinessProfile;
use App\Models\PageRegistry;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Governance\CatalogHierarchyService;
use App\Services\Governance\UniversalPageRegistry;
use App\Services\Governance\VisibilityGovernanceService;
use App\Services\Operations\CategoryDiscoveryEngine;
use App\Services\Seo\CategoryJsonLdBuilder;
use App\Services\Seo\DatabaseFirstComplianceValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('syncs category discovery seo schema and json ld from database', function () {
    BusinessProfile::query()->create([
        'name' => 'Medca',
        'city' => 'Mysuru',
        'website' => config('app.url'),
    ]);

    $category = ServiceCategory::query()->create([
        'name' => 'Medical Lab',
        'code' => 'medical-lab',
        'description' => 'Lab tests at home.',
        'is_active' => true,
        'is_featured' => true,
    ]);

    app(CategoryDiscoveryEngine::class)->sync($category);

    $category->refresh()->load(['seo', 'schema']);
    $graph = app(CategoryJsonLdBuilder::class)->buildGraph($category);

    expect($category->seo)->not->toBeNull()
        ->and($category->seo->meta_title)->toBe('Medical Lab')
        ->and($category->seo->canonical_url)->toContain('medical-lab')
        ->and($category->schema)->not->toBeNull()
        ->and($graph['@graph'])->not->toBeEmpty();
});

it('governs visibility through single service snapshots', function () {
    $category = ServiceCategory::factory()->create(['is_featured' => true, 'show_on_homepage' => true]);
    $service = Service::factory()->create([
        'is_featured' => true,
        'is_top_rated' => true,
        'show_on_homepage' => true,
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $governance = app(VisibilityGovernanceService::class);

    expect($governance->snapshotForCategory($category)['is_featured'])->toBeTrue()
        ->and($governance->snapshotForService($service)['is_top_rated'])->toBeTrue();
});

it('registers categories services and sub services in universal page registry', function () {
    $category = ServiceCategory::factory()->create(['code' => 'physiotherapy']);
    $service = Service::factory()->create([
        'service_code' => 'physio-home',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);
    SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'knee-rehab',
        'title' => 'Knee Rehab',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    app(UniversalPageRegistry::class)->syncAll();

    expect(PageRegistry::query()->where('registry_key', 'category:physiotherapy')->exists())->toBeTrue()
        ->and(PageRegistry::query()->where('entity_type', 'sub_service')->exists())->toBeTrue();
});

it('builds category service sub service hierarchy without conflicts', function () {
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);
    $category->services()->attach($service->id);
    SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'blood-test',
        'title' => 'Blood Test',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $tree = app(CatalogHierarchyService::class)->treeForCategory($category->fresh());
    $conflicts = app(CatalogHierarchyService::class)->detectConflicts();

    expect($tree['services'])->toHaveCount(1)
        ->and($tree['sub_services'])->toHaveCount(1)
        ->and($conflicts)->toBeEmpty();
});

it('passes database first compliance scan on core engines', function () {
    $result = app(DatabaseFirstComplianceValidator::class)->scanAppServices();

    expect($result['compliant'])->toBeTrue();
});
