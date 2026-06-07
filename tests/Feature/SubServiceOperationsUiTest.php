<?php

use App\Models\Service;
use App\Models\SubService;
use App\Models\User;
use App\ModuleAccess;

it('shows sub-services tab on service edit', function () {
    $service = Service::factory()->create();
    SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'night-care',
        'title' => 'Night Care',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    $this->actingAs($user)
        ->get(route('operations.services.edit', ['service' => $service, 'tab' => 'sub_services']))
        ->assertOk()
        ->assertSee('Night Care', false)
        ->assertSee(__('Sub-services'), false);
});

it('creates a sub-service from the manual form', function () {
    $service = Service::factory()->create(['service_code' => 'home-nursing']);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    $this->actingAs($user)
        ->post(route('operations.services.sub-services.store', $service), [
            'sub_service_code' => 'elder-monitor',
            'title' => 'Elder Monitoring',
            'short_summary' => '24x7 monitoring at home.',
            'publish_status' => 'published',
            'visibility' => 'public',
            'is_active' => '1',
        ])
        ->assertRedirect();

    expect(SubService::query()->where('sub_service_code', 'elder-monitor')->exists())->toBeTrue();
});
