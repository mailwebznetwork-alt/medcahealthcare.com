<?php

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServicePincode;
use App\Models\SubService;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Operations\ServicePincodeCoverageService;

it('propagates category pincodes to services with matching primary category', function () {
    $category = ServiceCategory::factory()->create(['code' => 'home-care', 'name' => 'Home Care']);
    $otherCategory = ServiceCategory::factory()->create(['code' => 'other-care', 'name' => 'Other Care']);
    $pinA = PinCode::factory()->create(['pincode' => '560076', 'is_active' => true, 'is_serviceable' => true]);
    $pinB = PinCode::factory()->create(['pincode' => '560078', 'is_active' => true, 'is_serviceable' => true]);

    $service = Service::factory()->create(['service_code' => 'nursing-care']);
    $service->categories()->sync([
        $category->id => ['is_primary' => true],
        $otherCategory->id => ['is_primary' => false],
    ]);

    $otherService = Service::factory()->create(['service_code' => 'physio-care']);
    $otherService->categories()->sync([
        $otherCategory->id => ['is_primary' => true],
        $category->id => ['is_primary' => false],
    ]);

    app(ServicePincodeCoverageService::class)->syncCategoryPincodes($category, [$pinA->id, $pinB->id]);

    $service->refresh()->load('pincodes');
    $otherService->refresh()->load('pincodes');

    expect($service->pincodes->pluck('id')->all())->toEqualCanonicalizing([$pinA->id, $pinB->id])
        ->and($service->pincodes->every(fn ($pin) => $pin->pivot->pin_source === ServicePincode::SOURCE_CATEGORY))->toBeTrue()
        ->and($otherService->pincodes)->toBeEmpty();
});

it('removes category pincodes from services when category pin is removed even if manually added', function () {
    $category = ServiceCategory::factory()->create(['code' => 'elder-care']);
    $pin = PinCode::factory()->create(['pincode' => '560095', 'is_active' => true, 'is_serviceable' => true]);
    $service = Service::factory()->create(['service_code' => 'elder-nursing']);
    $service->categories()->sync([$category->id => ['is_primary' => true]]);

    $coverage = app(ServicePincodeCoverageService::class);
    $coverage->syncCategoryPincodes($category, [$pin->id]);
    $coverage->applyServiceGeoSelection($service->fresh(), [$pin->id]);

    expect($service->fresh()->pincodes)->toHaveCount(1);

    $coverage->syncCategoryPincodes($category, []);

    expect($service->fresh()->pincodes)->toBeEmpty();
});

it('updates category pincodes through the operations UI', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $category = ServiceCategory::factory()->create([
        'name' => 'Caregiver Services',
        'code' => 'cat-caregiver-services',
    ]);
    $pin = PinCode::factory()->create(['pincode' => '560100', 'is_active' => true, 'is_serviceable' => true]);
    $service = Service::factory()->create(['service_code' => 'caregiver-visit']);
    $service->categories()->sync([$category->id => ['is_primary' => true]]);

    $this->actingAs($user)
        ->put(route('operations.service-categories.update', $category), [
            'name' => 'Caregiver Services',
            'code' => 'cat-caregiver-services',
            'publish_status' => 'published',
            'visibility' => 'public',
            'pincodes' => [$pin->id],
            'is_active' => '1',
        ])
        ->assertRedirect();

    expect($category->fresh()->pincodes->pluck('id')->all())->toBe([$pin->id])
        ->and($service->fresh()->pincodes->pluck('id')->all())->toBe([$pin->id]);
});

it('propagates category pincodes to sub-services through the parent service', function () {
    $category = ServiceCategory::factory()->create(['code' => 'care-team']);
    $pinA = PinCode::factory()->create(['pincode' => '560120', 'is_active' => true, 'is_serviceable' => true]);
    $pinB = PinCode::factory()->create(['pincode' => '560121', 'is_active' => true, 'is_serviceable' => true]);
    $service = Service::factory()->create(['service_code' => 'care-visit']);
    $service->categories()->sync([$category->id => ['is_primary' => true]]);
    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'day-shift',
        'title' => 'Day Shift',
        'is_active' => true,
        'publish_status' => 'published',
        'visibility' => 'public',
    ]);

    $coverage = app(ServicePincodeCoverageService::class);
    $coverage->syncCategoryPincodes($category, [$pinA->id, $pinB->id], 'ui', deferServicePropagation: true);
    $coverage->propagateCategoryToServices($category->fresh());

    expect($service->fresh()->pincodes->pluck('id')->all())->toEqualCanonicalizing([$pinA->id, $pinB->id])
        ->and($sub->fresh()->includedPincodeIds())->toEqualCanonicalizing([$pinA->id, $pinB->id]);
});

it('stores sub-service pincode exclusions without changing parent service coverage', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $service = Service::factory()->create();
    $pinA = PinCode::factory()->create(['pincode' => '560111', 'is_active' => true, 'is_serviceable' => true]);
    $pinB = PinCode::factory()->create(['pincode' => '560112', 'is_active' => true, 'is_serviceable' => true]);
    $service->pincodes()->sync([
        $pinA->id => ['pin_source' => ServicePincode::SOURCE_MANUAL],
        $pinB->id => ['pin_source' => ServicePincode::SOURCE_MANUAL],
    ]);

    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'night-shift',
        'title' => 'Night Shift',
        'is_active' => true,
        'publish_status' => 'published',
        'visibility' => 'public',
    ]);

    $this->actingAs($user)
        ->put(route('operations.services.sub-services.update', [$service, $sub]), [
            'sub_service_code' => 'night-shift',
            'title' => 'Night Shift',
            'publish_status' => 'published',
            'visibility' => 'public',
            'pincodes' => [$pinA->id],
            'is_active' => '1',
        ])
        ->assertRedirect();

    $sub->refresh()->load('pincodeExclusions');
    expect($sub->includedPincodeIds())->toBe([$pinA->id])
        ->and($sub->pincodeExclusions)->toHaveCount(1)
        ->and($sub->pincodeExclusions->first()->pincode_id)->toBe($pinB->id)
        ->and($service->fresh()->pincodes)->toHaveCount(2);
});
