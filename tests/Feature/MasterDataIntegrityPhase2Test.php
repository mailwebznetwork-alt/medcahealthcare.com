<?php

use App\Models\AdminDeletionTombstone;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Governance\CategoryCreationGuard;
use App\Services\Governance\MappingProtectionService;
use App\Services\Governance\ServiceCreationGuard;
use App\Services\Governance\SubServiceCreationGuard;
use App\Services\Import\CategoryEntityImporter;
use App\Services\Import\ServiceEntityImporter;
use App\Services\Import\SubServiceEntityImporter;
use App\Services\Launch\ProductionPopulationService;
use App\Services\Operations\ServiceCategoryService;
use App\Services\Operations\ServiceLifecycle;
use App\Services\Operations\SubServiceDeletionService;
use Database\Seeders\MedcaBangalorePinCodesSeeder;
use Database\Seeders\MedcaServiceCategoriesSeeder;

function phase2User(): User
{
    return User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);
}

it('records service tombstone and allows explicit import recreation', function () {
    $user = phase2User();
    $service = Service::factory()->create(['service_code' => 'test-svc-del', 'title' => 'Test']);

    $this->actingAs($user);
    app(ServiceLifecycle::class)->delete($service);

    expect(Service::query()->where('service_code', 'test-svc-del')->exists())->toBeFalse()
        ->and(AdminDeletionTombstone::exists('service', 'test-svc-del'))->toBeTrue();

    $result = app(ServiceEntityImporter::class)->importParsed([
        'headers' => ['service_code', 'title'],
        'rows' => [['test-svc-del', 'Resurrected']],
    ]);

    expect($result['created'] ?? 0)->toBe(1)
        ->and(Service::query()->where('service_code', 'test-svc-del')->exists())->toBeTrue()
        ->and(AdminDeletionTombstone::exists('service', 'test-svc-del'))->toBeFalse();
});

it('blocks seeder from recreating tombstoned service', function () {
    $user = phase2User();
    $service = Service::factory()->create(['service_code' => 'doctor-home-visit']);

    $this->actingAs($user);
    app(ServiceLifecycle::class)->delete($service);

    expect(app(ServiceCreationGuard::class)->canCreateService('doctor-home-visit', 'seeder'))->toBeFalse();
});

it('records category tombstone and allows explicit import recreation', function () {
    $user = phase2User();
    $category = ServiceCategory::factory()->create(['code' => 'test-cat-del', 'name' => 'Test Cat']);

    $this->actingAs($user);
    app(ServiceCategoryService::class)->delete($category);

    expect(ServiceCategory::query()->where('code', 'test-cat-del')->exists())->toBeFalse()
        ->and(AdminDeletionTombstone::exists('category', 'test-cat-del'))->toBeTrue();

    $result = app(CategoryEntityImporter::class)->importParsed([
        'headers' => ['code', 'name'],
        'rows' => [['test-cat-del', 'Resurrected Cat']],
    ]);

    expect($result['created'] + $result['updated'])->toBeGreaterThan(0)
        ->and(ServiceCategory::query()->where('code', 'test-cat-del')->exists())->toBeTrue()
        ->and(AdminDeletionTombstone::exists('category', 'test-cat-del'))->toBeFalse();
});

it('restores soft-deleted category by slug on explicit import when code differs', function () {
    $user = phase2User();
    $category = ServiceCategory::factory()->create([
        'code' => 'medical-lab-services',
        'name' => 'Medical Lab Services',
        'slug' => 'medical-lab-services',
    ]);

    $this->actingAs($user);
    app(ServiceCategoryService::class)->delete($category);

    $result = app(CategoryEntityImporter::class)->importParsed([
        'headers' => ['code', 'name'],
        'rows' => [['cat-lab', 'Medical Lab Services']],
    ]);

    expect($result['created'] + $result['updated'])->toBeGreaterThan(0)
        ->and(ServiceCategory::query()->where('code', 'cat-lab')->exists())->toBeTrue()
        ->and(AdminDeletionTombstone::exists('category', 'medical-lab-services'))->toBeFalse();
});

it('blocks category seeder from recreating tombstoned category', function () {
    $user = phase2User();
    $category = ServiceCategory::factory()->create(['code' => 'home-care', 'name' => 'Home Care']);

    $this->actingAs($user);
    app(ServiceCategoryService::class)->delete($category);

    (new MedcaServiceCategoriesSeeder)->run();

    expect(ServiceCategory::query()->where('code', 'home-care')->exists())->toBeFalse();
});

it('records sub service tombstone and allows explicit import recreation', function () {
    $user = phase2User();
    $service = Service::factory()->create(['service_code' => 'parent-svc']);
    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'sub-del-test',
        'title' => 'Sub Test',
        'is_active' => true,
        'publish_status' => \App\Enums\PublishStatus::Published,
        'visibility' => \App\Enums\ServiceVisibility::Public,
        'sort_order' => 0,
    ]);

    $this->actingAs($user);
    app(SubServiceDeletionService::class)->delete($sub);

    $key = 'parent-svc/sub-del-test';
    expect(AdminDeletionTombstone::exists('sub_service', $key))->toBeTrue()
        ->and(SubService::query()->where('sub_service_code', 'sub-del-test')->exists())->toBeFalse();

    $result = app(SubServiceEntityImporter::class)->importParsed([
        'headers' => ['parent_service_code', 'sub_service_code', 'title'],
        'rows' => [['parent-svc', 'sub-del-test', 'Resurrected Sub']],
    ]);

    expect($result['created'] ?? 0)->toBe(1)
        ->and(SubService::query()->where('sub_service_code', 'sub-del-test')->exists())->toBeTrue()
        ->and(AdminDeletionTombstone::exists('sub_service', $key))->toBeFalse();
});

it('blocks matrix sync from reattaching admin removed mappings', function () {
    $user = phase2User();
    $service = Service::factory()->create(['service_code' => 'map-svc', 'is_active' => true]);
    $pin = PinCode::factory()->create(['pincode' => '560888', 'city' => 'Bangalore', 'is_active' => true, 'is_serviceable' => true]);

    $service->pincodes()->sync([$pin->id]);
    expect($service->pincodes()->where('pincode_id', $pin->id)->exists())->toBeTrue();

    $this->actingAs($user);
    app(MappingProtectionService::class)->recordServicePincodeRemoval('map-svc', '560888', 'ui');
    $service->pincodes()->sync([]);

    $sync = [
        $pin->id => [
            'priority' => 10,
            'is_visible' => true,
            'is_featured' => false,
            'coverage_notes' => 'test',
        ],
    ];
    $filtered = app(MappingProtectionService::class)->filterSyncPayload($service->fresh(), $sync, 'populate');

    expect($filtered)->toBe([]);
});

it('blocks populate when master data protection enabled', function () {
    config(['master_data_protection.enabled' => true]);

    $result = app(ProductionPopulationService::class)->populate(false);

    expect($result['blocked'] ?? false)->toBeTrue();
});

it('preserves existing active services after unrelated deletions', function () {
    $keep = Service::factory()->create(['service_code' => 'keep-svc']);
    $remove = Service::factory()->create(['service_code' => 'remove-svc']);

    $this->actingAs(phase2User());
    app(ServiceLifecycle::class)->delete($remove);

    expect(Service::query()->whereKey($keep->id)->exists())->toBeTrue();
});

it('pincode integrity still holds after phase 2 changes', function () {
    expect(app(\App\Services\Governance\PinCodeCreationGuard::class)->canCreatePincode('560999', 'seeder'))->toBeTrue();

    (new MedcaBangalorePinCodesSeeder)->run();

    expect(PinCode::query()->where('pincode', '560076')->exists())->toBeTrue();
});
