<?php

use App\Models\BusinessProfile;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Import\ImportRegistry;
use App\Services\Operations\ServiceLocationMatrixPivot;
use App\Services\Operations\ServiceLocationMatrixReconciler;
use App\Services\Seo\LocalityContextResolver;
use App\Services\Seo\SeoOwnershipGuard;
use App\Services\Seo\UnifiedJsonLdGraphBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates sub services with parent relationship and schema support', function () {
    $service = Service::factory()->create([
        'title' => 'Medical Lab',
        'service_code' => 'medical-lab',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'blood-test',
        'title' => 'Blood Test',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
        'sort_order' => 1,
        'is_featured' => true,
    ]);

    $sub->seo()->create(['meta_title' => 'Blood Test at Home']);

    $graph = app(UnifiedJsonLdGraphBuilder::class)->buildServiceGraph($service->fresh(['subServices.seo']));

    $serviceNode = collect($graph['@graph'])->first(fn ($n) => ($n['@id'] ?? '') === $service->publicUrl().'#service');

    expect($sub->service_id)->toBe($service->id)
        ->and($service->subServices)->toHaveCount(1)
        ->and($serviceNode['hasPart'] ?? [])->not->toBeEmpty()
        ->and($serviceNode['hasPart'][0]['name'])->toBe('Blood Test');
});

it('evaluates matrix pivot visibility and category filters', function () {
    $category = ServiceCategory::query()->create([
        'name' => 'Diagnostics',
        'code' => 'diagnostics',
        'is_active' => true,
    ]);

    $service = Service::factory()->create(['publish_status' => 'published', 'visibility' => 'public', 'is_active' => true]);
    $service->categories()->attach($category->id);

    $pin = PinCode::factory()->create(['is_active' => true]);
    $service->pincodes()->attach($pin->id, [
        'is_visible' => true,
        'priority' => 10,
        'category_filter_ids' => [$category->id],
    ]);

    $service->load('pincodes');
    $pin = $service->pincodes->first();

    expect(ServiceLocationMatrixPivot::isActive($service, $pin))->toBeTrue()
        ->and(ServiceLocationMatrixPivot::priority($service, $pin))->toBe(10);

    $service->pincodes()->updateExistingPivot($pin->id, ['is_visible' => false]);
    $service->load('pincodes');
    $pin = $service->pincodes->first();

    expect(ServiceLocationMatrixPivot::isActive($service, $pin))->toBeFalse();
});

it('resolves locality from business profile not hardcoded city', function () {
    BusinessProfile::query()->create([
        'name' => 'Medca',
        'city' => 'Mysuru',
        'website' => config('app.url'),
    ]);

    $resolver = app(LocalityContextResolver::class);

    expect($resolver->primaryCity())->toBe('Mysuru');
});

it('prevents growth layer seo mirror by default', function () {
    expect(SeoOwnershipGuard::operationsOwnsServiceUrls())->toBeTrue()
        ->and(SeoOwnershipGuard::shouldMirrorServiceToGrowthLayer())->toBeFalse();
});

it('reconciles service location matrix without errors', function () {
    $service = Service::factory()->create([
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);
    $pin = PinCode::factory()->create(['is_active' => true, 'is_serviceable' => true]);
    $service->pincodes()->attach($pin->id, ['is_visible' => true, 'priority' => 5]);

    $report = app(ServiceLocationMatrixReconciler::class)->reconcile($service->fresh());

    expect($report['services_processed'])->toBe(1)
        ->and($report['pages_provisioned'] + $report['pages_updated'])->toBeGreaterThanOrEqual(1);
});

it('registers catalog importers in import registry', function () {
    $registry = app(ImportRegistry::class);

    expect($registry->registeredEntities())->toContain(
        'categories', 'services', 'sub_services', 'pincodes', 'geo', 'mappings'
    )->and($registry->resolve('pincodes')->entityKey())->toBe('pincodes');
});
