<?php

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use App\Services\Operations\CategoryMasterOrchestrator;
use App\Services\Operations\ServiceLocationPageProvisioner;
use App\Services\Operations\ServiceMasterOrchestrator;
use App\Services\Operations\SubServiceMasterOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('hard deletes location pages when pincode is removed from service pivot', function () {
    $service = Service::factory()->create([
        'service_code' => 'strict-removal-svc',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);
    $pin = PinCode::factory()->create(['pincode' => '560199', 'is_active' => true, 'is_serviceable' => true]);
    $service->pincodes()->attach($pin->id, ['is_visible' => true, 'priority' => 1]);

    app(ServiceLocationPageProvisioner::class)->syncAllForService($service->fresh(['pincodes']));

    expect(ServiceLocationPage::query()->where('service_id', $service->id)->count())->toBe(1);

    $service->pincodes()->detach($pin->id);
    app(ServiceLocationPageProvisioner::class)->syncAllForService($service->fresh(['pincodes']));

    expect(ServiceLocationPage::query()->where('service_id', $service->id)->count())->toBe(0)
        ->and(Page::query()->where('slug', 'service-strict-removal-svc-loc-560199')->exists())->toBeFalse();
});

it('hard deletes location pages when service is deactivated and recreates on reactivation', function () {
    $service = Service::factory()->create([
        'service_code' => 'strict-cycle-svc',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);
    $pin = PinCode::factory()->create(['pincode' => '560200', 'is_active' => true, 'is_serviceable' => true]);
    $service->pincodes()->attach($pin->id, ['is_visible' => true, 'priority' => 1]);

    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema']));

    expect($service->fresh()->detail_page_id)->not->toBeNull()
        ->and(ServiceLocationPage::query()->where('service_id', $service->id)->count())->toBe(1);

    $service->forceFill(['is_active' => false])->save();
    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema']));

    $service = $service->fresh();
    expect($service->detail_page_id)->toBeNull()
        ->and(ServiceLocationPage::query()->where('service_id', $service->id)->count())->toBe(0)
        ->and(Page::query()->where('slug', 'service-strict-cycle-svc')->exists())->toBeFalse()
        ->and(Page::query()->where('slug', 'service-strict-cycle-svc-loc-560200')->exists())->toBeFalse();

    $service->forceFill(['is_active' => true])->save();
    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema']));

    expect($service->fresh()->detail_page_id)->not->toBeNull()
        ->and(ServiceLocationPage::query()->where('service_id', $service->id)->count())->toBe(1);
});

it('hard deletes inactive matrix pivot location pages instead of deactivating them', function () {
    $service = Service::factory()->create([
        'service_code' => 'strict-pivot-svc',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);
    $pin = PinCode::factory()->create(['pincode' => '560201', 'is_active' => true, 'is_serviceable' => true]);
    $service->pincodes()->attach($pin->id, ['is_visible' => true, 'priority' => 1]);

    app(ServiceLocationPageProvisioner::class)->syncAllForService($service->fresh(['pincodes']));
    expect(Page::query()->where('slug', 'service-strict-pivot-svc-loc-560201')->exists())->toBeTrue();

    $service->pincodes()->updateExistingPivot($pin->id, ['is_visible' => false]);
    app(ServiceLocationPageProvisioner::class)->syncAllForService($service->fresh(['pincodes']));

    expect(ServiceLocationPage::query()->where('service_id', $service->id)->count())->toBe(0)
        ->and(Page::query()->where('slug', 'service-strict-pivot-svc-loc-560201')->exists())->toBeFalse();
});

it('hard deletes category pages when category is deactivated and recreates on reactivation', function () {
    $pin = PinCode::factory()->create(['pincode' => '560301', 'is_active' => true, 'is_serviceable' => true]);
    $category = ServiceCategory::factory()->create([
        'code' => 'strict-category',
        'slug' => 'strict-category',
        'is_active' => true,
    ]);
    $category->pincodes()->attach($pin->id);

    app(CategoryMasterOrchestrator::class)->sync($category->fresh(['seo', 'faqs', 'schema', 'pincodes']));

    expect($category->fresh()->page_id)->not->toBeNull()
        ->and(Page::query()->where('slug', 'category-strict-category')->exists())->toBeTrue();

    $category->forceFill(['is_active' => false])->save();
    app(CategoryMasterOrchestrator::class)->sync($category->fresh(['seo', 'faqs', 'schema', 'pincodes']));

    expect($category->fresh()->page_id)->toBeNull()
        ->and(Page::query()->where('slug', 'category-strict-category')->exists())->toBeFalse();

    $category->forceFill(['is_active' => true])->save();
    app(CategoryMasterOrchestrator::class)->sync($category->fresh(['seo', 'faqs', 'schema', 'pincodes']));

    expect($category->fresh()->page_id)->not->toBeNull()
        ->and(Page::query()->where('slug', 'category-strict-category')->exists())->toBeTrue();
});

it('hard deletes sub-service pages when deactivated and recreates on reactivation', function () {
    $pin = PinCode::factory()->create(['pincode' => '560302', 'is_active' => true, 'is_serviceable' => true]);
    $service = Service::factory()->create([
        'service_code' => 'strict-sub-parent',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);
    $service->pincodes()->attach($pin->id, ['is_visible' => true, 'priority' => 1]);

    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'strict-sub',
        'title' => 'Strict Sub',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    app(SubServiceMasterOrchestrator::class)->sync($sub->fresh(['seo', 'faqs', 'schema', 'service']));

    expect($sub->fresh()->page_id)->not->toBeNull()
        ->and(Page::query()->where('slug', 'service-strict-sub-parent-sub-strict-sub')->exists())->toBeTrue();

    $sub->forceFill(['is_active' => false])->save();
    app(SubServiceMasterOrchestrator::class)->sync($sub->fresh(['seo', 'faqs', 'schema', 'service']));

    expect($sub->fresh()->page_id)->toBeNull()
        ->and(Page::query()->where('slug', 'service-strict-sub-parent-sub-strict-sub')->exists())->toBeFalse();

    $sub->forceFill(['is_active' => true])->save();
    app(SubServiceMasterOrchestrator::class)->sync($sub->fresh(['seo', 'faqs', 'schema', 'service']));

    expect($sub->fresh()->page_id)->not->toBeNull()
        ->and(Page::query()->where('slug', 'service-strict-sub-parent-sub-strict-sub')->exists())->toBeTrue();
});

it('hard deletes sub-service pages when parent service is deactivated and recreates with service sync', function () {
    $pin = PinCode::factory()->create(['pincode' => '560303', 'is_active' => true, 'is_serviceable' => true]);
    $service = Service::factory()->create([
        'service_code' => 'strict-sub-cascade',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);
    $service->pincodes()->attach($pin->id, ['is_visible' => true, 'priority' => 1]);

    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'cascade-sub',
        'title' => 'Cascade Sub',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema', 'subServices']));

    expect($sub->fresh()->page_id)->not->toBeNull();

    $service->forceFill(['is_active' => false])->save();
    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema', 'subServices']));

    expect($sub->fresh()->page_id)->toBeNull()
        ->and(Page::query()->where('slug', 'service-strict-sub-cascade-sub-cascade-sub')->exists())->toBeFalse();

    $service->forceFill(['is_active' => true])->save();
    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema', 'subServices']));

    expect($sub->fresh()->page_id)->not->toBeNull();
});
