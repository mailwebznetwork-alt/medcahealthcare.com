<?php

use App\Models\ImportBatch;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Import\ImportPipeline;
use App\Services\Import\ImportRegistry;
use App\Services\Import\ImportRollbackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

it('registers phase 3 catalog importers', function () {
    $entities = app(ImportRegistry::class)->registeredEntities();

    expect($entities)->toContain('categories', 'services', 'sub_services', 'pincodes', 'geo', 'mappings');
});

it('imports categories from csv with seo and faq', function () {
    $path = storage_path('framework/testing/categories.csv');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, implode("\n", [
        'code,name,description,meta_title,faq_pairs',
        'home-care,Home Care,Premium home care.,Home Care Bangalore,What is home care?|Care at your doorstep.',
    ]));

    $result = app(ImportPipeline::class)->commit('categories', $path, null, 'categories.csv', false);

    expect($result['created'])->toBe(1)
        ->and(ServiceCategory::query()->where('code', 'home-care')->exists())->toBeTrue()
        ->and(ImportBatch::query()->where('entity_key', 'categories')->exists())->toBeTrue();
});

it('imports services and sub services from csv', function () {
    $catPath = storage_path('framework/testing/cat.csv');
    File::put($catPath, "code,name\nmed-lab,Medical Lab\n");

    $svcPath = storage_path('framework/testing/services.csv');
    File::put($svcPath, "service_code,title,category_codes,description\nblood-panel,Blood Panel,med-lab,Full blood work.\n");

    $subPath = storage_path('framework/testing/sub.csv');
    File::put($subPath, "parent_service_code,sub_service_code,title\nblood-panel,cbc-test,CBC Test\n");

    $pipeline = app(ImportPipeline::class);
    $pipeline->commit('categories', $catPath, null, null, false);
    $svc = $pipeline->commit('services', $svcPath, null, null, false);
    $sub = $pipeline->commit('sub_services', $subPath, null, null, false);

    expect($svc['created'])->toBe(1)
        ->and($sub['created'])->toBe(1)
        ->and(SubService::query()->where('sub_service_code', 'cbc-test')->exists())->toBeTrue();
});

it('imports pincode geo enrichment and service mapping', function () {
    $service = Service::factory()->create(['service_code' => 'nursing']);

    $geoPath = storage_path('framework/testing/geo.csv');
    File::put($geoPath, "pincode,area_name,city,coverage_text,landmark_names,hospital_names\n570001,VV Mohalla,Mysuru,24x7 coverage,Palace|Railway Station,Apollo|JSS\n");

    $mapPath = storage_path('framework/testing/map.csv');
    File::put($mapPath, "service_code,pincode,priority,is_visible\nnursing,570001,10,true\n");

    $pipeline = app(ImportPipeline::class);
    $geo = $pipeline->commit('geo', $geoPath, null, null, false);
    $map = $pipeline->commit('mappings', $mapPath, null, null, false);

    $pin = PinCode::query()->where('pincode', '570001')->first();

    expect($geo['created'])->toBe(1)
        ->and($map['created'])->toBe(1)
        ->and($pin)->not->toBeNull()
        ->and($pin->landmarks()->count())->toBeGreaterThan(0)
        ->and($service->fresh()->pincodes)->toHaveCount(1);
});

it('rolls back a committed import batch', function () {
    $path = storage_path('framework/testing/rollback-cat.csv');
    File::put($path, "code,name\nrollback-test,Rollback Test\n");

    $result = app(ImportPipeline::class)->commit('categories', $path, null, null, false);
    $batchId = $result['batch_id'];

    expect(ServiceCategory::query()->where('code', 'rollback-test')->exists())->toBeTrue();

    $rollback = app(ImportRollbackService::class)->rollback($batchId);

    expect($rollback['reverted'])->toBeGreaterThan(0)
        ->and(ServiceCategory::query()->where('code', 'rollback-test')->exists())->toBeFalse();
});
