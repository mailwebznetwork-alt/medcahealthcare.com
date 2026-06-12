<?php

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use App\Services\Operations\CategoryPageProvisioner;
use App\Services\Operations\ServiceLocationPageProvisioner;
use App\Services\Operations\ServiceMasterOrchestrator;
use App\Services\Operations\SubServicePageProvisioner;

it('deletes service location pages when a pincode is deleted', function () {
    $suffix = (string) random_int(100000, 999999);
    $pin = PinCode::factory()->create([
        'pincode' => $suffix,
        'area_name' => 'Cascade Delete Area',
        'is_active' => true,
        'is_serviceable' => true,
    ]);

    $service = Service::factory()->create([
        'service_code' => 'cascade-pin-'.$suffix,
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $service->pincodes()->attach($pin->id);
    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema']));

    $mapping = ServiceLocationPage::query()
        ->where('service_id', $service->id)
        ->where('pincode_id', $pin->id)
        ->firstOrFail();
    $pageId = $mapping->page_id;

    $pin->delete();

    expect(PinCode::query()->whereKey($pin->id)->exists())->toBeFalse()
        ->and(ServiceLocationPage::query()->whereKey($mapping->id)->exists())->toBeFalse()
        ->and(Page::query()->whereKey($pageId)->exists())->toBeFalse();
});

it('updates service location pages when a pincode is modified', function () {
    $suffix = (string) random_int(100000, 999999);
    $pin = PinCode::factory()->create([
        'pincode' => $suffix,
        'area_name' => 'Old Area Name',
        'city' => 'Bangalore',
        'is_active' => true,
        'is_serviceable' => true,
    ]);

    $service = Service::factory()->create([
        'service_code' => 'cascade-upd-'.$suffix,
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $service->pincodes()->attach($pin->id);
    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema']));

    $pageId = ServiceLocationPage::query()
        ->where('service_id', $service->id)
        ->where('pincode_id', $pin->id)
        ->value('page_id');

    $pin->update(['area_name' => 'Updated Area Name']);

    $title = Page::query()->whereKey($pageId)->value('title');

    expect($title)->toContain('Updated Area Name');
});

it('removes location pages when a service pincode is detached', function () {
    $suffix = (string) random_int(100000, 999999);
    $pin = PinCode::factory()->create(['pincode' => $suffix, 'is_active' => true, 'is_serviceable' => true]);
    $service = Service::factory()->create([
        'service_code' => 'detach-pin-'.$suffix,
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $service->pincodes()->attach($pin->id);
    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema']));

    $pageId = ServiceLocationPage::query()
        ->where('service_id', $service->id)
        ->where('pincode_id', $pin->id)
        ->value('page_id');

    $service->pincodes()->sync([]);
    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema']));

    expect(ServiceLocationPage::query()->where('service_id', $service->id)->where('pincode_id', $pin->id)->exists())->toBeFalse()
        ->and(Page::query()->whereKey($pageId)->exists())->toBeFalse();
});

it('deletes the owned category page when a category is deleted', function () {
    $pin = PinCode::factory()->create(['pincode' => '560304', 'is_active' => true, 'is_serviceable' => true]);
    $category = ServiceCategory::factory()->create([
        'code' => 'cascade-cat',
        'name' => 'Cascade Category',
        'is_active' => true,
    ]);
    $category->pincodes()->attach($pin->id);

    $page = app(CategoryPageProvisioner::class)->syncFromCategory($category->fresh(['pincodes']));
    $pageId = $page->id;

    $category->delete();

    expect(ServiceCategory::query()->whereKey($category->id)->exists())->toBeFalse()
        ->and(Page::query()->whereKey($pageId)->exists())->toBeFalse();
});

it('deletes the owned sub-service page when a sub-service is deleted', function () {
    $pin = PinCode::factory()->create(['pincode' => '560305', 'is_active' => true, 'is_serviceable' => true]);
    $service = Service::factory()->create([
        'service_code' => 'parent-svc',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);
    $service->pincodes()->attach($pin->id, ['is_visible' => true, 'priority' => 1]);

    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'cascade-sub',
        'title' => 'Cascade Sub Service',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $page = app(SubServicePageProvisioner::class)->syncFromSubService($sub->fresh(['service']));
    $pageId = $page->id;

    $sub->delete();

    expect(SubService::query()->whereKey($sub->id)->exists())->toBeFalse()
        ->and(Page::query()->whereKey($pageId)->exists())->toBeFalse();
});

it('purges registry entries when location mappings are removed', function () {
    $suffix = (string) random_int(100000, 999999);
    $pin = PinCode::factory()->create(['pincode' => $suffix, 'is_active' => true, 'is_serviceable' => true]);
    $service = Service::factory()->create([
        'service_code' => 'registry-purge-'.$suffix,
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $service->pincodes()->attach($pin->id);
    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema']));

    $mapping = ServiceLocationPage::query()
        ->where('service_id', $service->id)
        ->where('pincode_id', $pin->id)
        ->firstOrFail();

    app(ServiceLocationPageProvisioner::class)->removeMappingAndPage($mapping);

    expect(\App\Models\PageRegistry::query()
        ->where('registry_key', 'location:'.$service->service_code.':'.$suffix)
        ->exists())->toBeFalse();
});
