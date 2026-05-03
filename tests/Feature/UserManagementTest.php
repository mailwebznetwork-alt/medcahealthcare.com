<?php

use App\Models\User;
use App\ModuleAccess;

function umAll(bool $on): array
{
    return collect(ModuleAccess::keys())
        ->mapWithKeys(fn (string $k) => [$k => $on])
        ->all();
}

it('lists users for administrators with user management access', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => umAll(true),
    ]);

    $this->actingAs($admin)
        ->get(route('user-management.index'))
        ->assertOk();
});

it('forbids user management routes without module access', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => array_merge(umAll(true), [
            ModuleAccess::USER_MANAGEMENT => false,
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('user-management.index'))
        ->assertForbidden();
});

it('prevents non-root users from updating the root super administrator', function () {
    $root = User::factory()->rootSuperAdmin()->create([
        'email_verified_at' => now(),
    ]);

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => umAll(true),
    ]);

    $this->actingAs($admin)
        ->get(route('user-management.edit', $root))
        ->assertForbidden();
});

it('prevents deleting the root super administrator', function () {
    $root = User::factory()->rootSuperAdmin()->create([
        'email_verified_at' => now(),
    ]);

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => umAll(true),
    ]);

    $this->actingAs($admin)
        ->delete(route('user-management.destroy', $root))
        ->assertForbidden();
});

it('allows the root super administrator to edit another user', function () {
    $root = User::factory()->rootSuperAdmin()->create([
        'email_verified_at' => now(),
        'module_access' => umAll(true),
    ]);

    $other = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => umAll(true),
        'role_label' => 'Staff',
    ]);

    $this->actingAs($root)
        ->get(route('user-management.edit', $other))
        ->assertOk();
});

it('logs out inactive users on the next request', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => false,
        'module_access' => umAll(true),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
