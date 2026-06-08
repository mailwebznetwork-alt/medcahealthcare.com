<?php

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Operations\PinCodeDeletionService;
use App\Services\Operations\ServiceCategoryService;
use App\Services\Operations\ServiceLifecycle;
use App\Services\Operations\SubServiceDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('purges orphan service detail cms pages', function () {
    $orphan = Page::factory()->create([
        'slug' => 'service-orphan-detail',
        'registry_owner' => 'operations_service',
        'page_source' => 'generated',
    ]);

    $result = app(DownstreamArtifactPurger::class)->purgeOrphanServiceDetailPages();

    expect($result['pages_removed'])->toBe(1)
        ->and(Page::query()->whereKey($orphan->id)->exists())->toBeFalse();
});

it('purges orphan sub-service cms pages', function () {
    $orphan = Page::factory()->create([
        'slug' => 'service-elder-care-sub-orphan',
        'registry_owner' => 'operations_sub_service',
        'page_source' => 'generated',
    ]);

    $result = app(DownstreamArtifactPurger::class)->purgeOrphanSubServicePages();

    expect($result['pages_removed'])->toBe(1)
        ->and(Page::query()->whereKey($orphan->id)->exists())->toBeFalse();
});

it('purges orphan category cms pages', function () {
    $orphan = Page::factory()->create([
        'slug' => 'category-orphan-home-care',
        'registry_owner' => 'operations_category',
        'page_source' => 'generated',
    ]);

    $result = app(DownstreamArtifactPurger::class)->purgeOrphanCategoryPages();

    expect($result['pages_removed'])->toBe(1)
        ->and(Page::query()->whereKey($orphan->id)->exists())->toBeFalse();
});

it('does not purge catalog pages still linked to live entities', function () {
    $service = Service::factory()->create([
        'service_code' => 'linked-svc',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);
    $detailPage = Page::factory()->create([
        'slug' => 'service-linked-svc',
        'registry_owner' => 'operations_service',
    ]);
    $service->forceFill(['detail_page_id' => $detailPage->id])->save();

    $category = ServiceCategory::withoutEvents(fn () => ServiceCategory::factory()->create(['code' => 'linked-cat']));
    $categoryPage = Page::factory()->create([
        'slug' => 'category-linked-cat',
        'registry_owner' => 'operations_category',
    ]);
    $category->forceFill(['page_id' => $categoryPage->id])->saveQuietly();

    $sub = SubService::withoutEvents(fn () => SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'linked-sub',
        'title' => 'Linked Sub',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]));
    $subPage = Page::factory()->create([
        'slug' => 'service-linked-svc-sub-linked-sub',
        'registry_owner' => 'operations_sub_service',
    ]);
    $sub->forceFill(['page_id' => $subPage->id])->save();

    $result = app(DownstreamArtifactPurger::class)->purgeAllCatalogOrphans();

    expect($result['service_pages_removed'])->toBe(0)
        ->and($result['sub_service_pages_removed'])->toBe(0)
        ->and($result['category_pages_removed'])->toBe(0)
        ->and(Page::query()->whereKey($detailPage->id)->exists())->toBeTrue()
        ->and(Page::query()->whereKey($subPage->id)->exists())->toBeTrue()
        ->and(Page::query()->whereKey($categoryPage->id)->exists())->toBeTrue();
});

it('auto purges stray location pages after pincode deletion service runs', function () {
    $pin = PinCode::factory()->create(['pincode' => '921196', 'is_active' => true]);

    $stray = Page::factory()->create([
        'slug' => 'service-elder-care-loc-921196',
        'registry_owner' => 'operations_location_matrix',
        'page_source' => 'generated',
    ]);

    PageRegistry::query()->create([
        'page_id' => $stray->id,
        'entity_type' => 'page',
        'entity_id' => $stray->id,
        'registry_key' => 'page:'.$stray->slug,
        'page_category' => 'location',
        'owner' => 'operations_location_matrix',
        'source' => 'generated',
        'public_path' => '/services/elder-care/921196',
        'is_listed' => true,
    ]);

    app(PinCodeDeletionService::class)->delete($pin, 'ui');

    expect(Page::query()->whereKey($stray->id)->exists())->toBeFalse()
        ->and(PageRegistry::query()->where('registry_key', 'page:'.$stray->slug)->exists())->toBeFalse();
});

it('auto purges stray service page after service lifecycle delete', function () {
    $service = Service::factory()->create([
        'service_code' => 'auto-purge-svc',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $stray = Page::factory()->create([
        'slug' => 'service-auto-purge-svc',
        'registry_owner' => 'operations_service',
        'page_source' => 'generated',
    ]);

    app(ServiceLifecycle::class)->delete($service->fresh());

    expect(Service::query()->whereKey($service->id)->exists())->toBeFalse()
        ->and(Page::query()->whereKey($stray->id)->exists())->toBeFalse();
});

it('auto purges stray sub-service page after sub-service deletion service runs', function () {
    $service = Service::factory()->create(['service_code' => 'parent-auto-purge']);

    $stray = Page::factory()->create([
        'slug' => 'service-parent-auto-purge-sub-stray-only',
        'registry_owner' => 'operations_sub_service',
        'page_source' => 'generated',
    ]);

    $sub = SubService::withoutEvents(fn () => SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'auto-purge-sub',
        'title' => 'Auto Purge Sub',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]));

    app(SubServiceDeletionService::class)->delete($sub, 'ui');

    expect(SubService::query()->whereKey($sub->id)->exists())->toBeFalse()
        ->and(Page::query()->whereKey($stray->id)->exists())->toBeFalse();
});

it('auto purges stray category page after category deletion service runs', function () {
    $stray = Page::factory()->create([
        'slug' => 'category-stray-only',
        'registry_owner' => 'operations_category',
        'page_source' => 'generated',
    ]);

    $category = ServiceCategory::withoutEvents(fn () => ServiceCategory::factory()->create(['code' => 'auto-purge-cat']));

    app(ServiceCategoryService::class)->delete($category);

    expect(ServiceCategory::query()->whereKey($category->id)->exists())->toBeFalse()
        ->and(Page::query()->whereKey($stray->id)->exists())->toBeFalse();
});
