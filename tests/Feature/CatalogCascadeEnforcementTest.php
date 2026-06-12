<?php

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Operations\ServicePincodeCoverageService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('propagates category geo to services on save without manual artisan', function () {
    $category = ServiceCategory::factory()->create(['code' => 'cat-test-cascade']);
    $pinA = PinCode::factory()->create(['pincode' => '560301', 'is_active' => true, 'is_serviceable' => true]);
    $pinB = PinCode::factory()->create(['pincode' => '560302', 'is_active' => true, 'is_serviceable' => true]);
    $service = Service::factory()->create(['service_code' => 'cascade-test-svc', 'publish_status' => 'published', 'visibility' => 'public']);
    $service->categories()->sync([$category->id => ['is_primary' => true]]);

    $user = User::factory()->create(['role' => 'admin', 'module_access' => ModuleAccess::defaultGrants()]);

    $this->actingAs($user)
        ->put(route('operations.service-categories.update', $category), [
            'name' => $category->name,
            'code' => $category->code,
            'publish_status' => 'published',
            'visibility' => 'public',
            'pincodes' => [$pinA->id, $pinB->id],
            'is_active' => '1',
        ])
        ->assertRedirect();

    expect($service->fresh()->pincodes->pluck('id')->all())
        ->toEqualCanonicalizing([$pinA->id, $pinB->id]);
});

it('runs import post sync inline when staging commits', function () {
    $coverage = app(ServicePincodeCoverageService::class);
    $category = ServiceCategory::factory()->create(['code' => 'cat-import-cascade']);
    $service = Service::factory()->create(['service_code' => 'import-cascade-svc', 'publish_status' => 'published', 'visibility' => 'public']);
    $service->categories()->sync([$category->id => ['is_primary' => true]]);

    $ran = app(\App\Services\Import\ImportPostSyncService::class)->syncForEntities(['categories']);

    expect($ran)->toContain('medca:propagate-all-category-pincodes');
});
