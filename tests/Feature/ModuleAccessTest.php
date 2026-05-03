<?php

use App\Models\User;
use App\ModuleAccess;

function moduleAccessAll(bool $value): array
{
    return collect(ModuleAccess::keys())
        ->mapWithKeys(fn (string $k) => [$k => $value])
        ->all();
}

it('returns forbidden when the user lacks module access', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => array_merge(moduleAccessAll(true), [
            ModuleAccess::MARKETING => false,
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('modules.marketing'))
        ->assertForbidden();
});

it('does not render sidebar links for modules the user cannot access', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => array_merge(moduleAccessAll(true), [
            ModuleAccess::MARKETING => false,
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('modules.marketing'), false);
});

it('allows an administrator to update another user module access', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => array_merge(moduleAccessAll(true), []),
    ]);

    $subject = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => moduleAccessAll(true),
    ]);

    $payload = moduleAccessAll(true);
    $payload[ModuleAccess::OPERATIONS] = false;

    $this->actingAs($admin)
        ->put(route('user-management.update', $subject), [
            'name' => $subject->name,
            'email' => $subject->email,
            'phone' => $subject->phone,
            'role_label' => $subject->role_label,
            'module_access' => $payload,
            'is_active' => 1,
        ])
        ->assertRedirect(route('user-management.index'));

    expect($subject->fresh()->hasModuleAccess(ModuleAccess::OPERATIONS))->toBeFalse();
});

it('blocks the dashboard when dashboard access is disabled', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => array_merge(moduleAccessAll(true), [
            ModuleAccess::DASHBOARD => false,
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});
