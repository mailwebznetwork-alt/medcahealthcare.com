<?php

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Operations\CatalogGeoCoverageEnforcer;
use App\Services\Operations\CategoryMasterOrchestrator;
use App\Services\Operations\ServiceMasterOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('removes all generated catalog pages when every pincode is deleted', function () {
    $pin = PinCode::factory()->create(['pincode' => '560300', 'is_active' => true, 'is_serviceable' => true]);

    $category = ServiceCategory::factory()->create(['code' => 'geo-cat', 'slug' => 'geo-cat', 'is_active' => true]);
    $category->pincodes()->attach($pin->id);

    $service = Service::factory()->create([
        'service_code' => 'geo-svc',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);
    $service->pincodes()->attach($pin->id, ['is_visible' => true, 'priority' => 1]);

    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'geo-sub',
        'title' => 'Geo Sub',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    Page::factory()->create(['slug' => 'manual-only-page', 'page_source' => 'manual']);

    app(CategoryMasterOrchestrator::class)->sync($category->fresh(['pincodes', 'seo', 'faqs', 'schema']));
    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema', 'subServices']));
    app(\App\Services\Operations\SubServiceMasterOrchestrator::class)->sync($sub->fresh(['seo', 'faqs', 'schema', 'service']));

    expect(Page::query()->where('page_source', 'generated')->count())->toBeGreaterThan(0);

    app(CatalogGeoCoverageEnforcer::class)->detachPivotsForPinIds([$pin->id]);
    $pin->delete();
    app(CatalogGeoCoverageEnforcer::class)->enforceAfterGeoRemoval();

    expect(PinCode::query()->count())->toBe(0)
        ->and(Page::query()->where('page_source', 'generated')->where('page_category', '!=', 'sub_service')->count())->toBe(0)
        ->and(Page::query()->where('page_category', 'sub_service')->where('page_source', 'generated')->count())->toBe(1)
        ->and(Page::query()->where('page_source', 'manual')->count())->toBe(1)
        ->and(Page::query()->where('slug', 'manual-only-page')->exists())->toBeTrue()
        ->and($sub->fresh()->page_id)->not->toBeNull();
});

it('blocks generated service pages when service has no active pincode coverage', function () {
    $service = Service::factory()->create([
        'service_code' => 'no-geo-svc',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema']));

    expect($service->fresh()->detail_page_id)->toBeNull()
        ->and(Page::query()->where('slug', 'service-no-geo-svc')->exists())->toBeFalse();
});
