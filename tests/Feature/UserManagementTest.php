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

it('prevents modifying user management read-only accounts by display name', function () {
    config([
        'user_management.profile_readonly_emails' => [],
        'user_management.profile_readonly_names' => ['locked persona'],
    ]);

    $locked = User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Locked Persona',
        'module_access' => umAll(true),
    ]);

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => umAll(true),
    ]);

    $this->actingAs($admin)
        ->get(route('user-management.edit', $locked))
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch(route('user-management.deactivate', $locked))
        ->assertForbidden();

    $this->actingAs($admin)
        ->delete(route('user-management.destroy', $locked))
        ->assertForbidden();
});

it('omits profile read-only users from the user management index', function () {
    config([
        'user_management.profile_readonly_emails' => [],
        'user_management.profile_readonly_names' => ['hidden from index'],
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Hidden From Index',
        'module_access' => umAll(true),
    ]);

    User::factory()->create([
        'email_verified_at' => now(),
        'name' => 'Visible Colleague',
        'module_access' => umAll(true),
    ]);

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => umAll(true),
    ]);

    $this->actingAs($admin)
        ->get(route('user-management.index'))
        ->assertOk()
        ->assertSee('Visible Colleague')
        ->assertDontSee('Hidden From Index');
});

it('hides the root super administrator from the index by default', function () {
    config([
        'user_management.hide_root_account_in_directory' => true,
        'user_management.profile_readonly_emails' => [],
        'user_management.profile_readonly_names' => ['momjerrie'],
    ]);

    $root = User::factory()->rootSuperAdmin()->create([
        'email_verified_at' => now(),
        'module_access' => umAll(true),
    ]);

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => umAll(true),
    ]);

    $this->actingAs($admin)
        ->get(route('user-management.index'))
        ->assertOk()
        ->assertDontSee($root->email);
});

it('prevents modifying user management read-only accounts by email', function () {
    config([
        'user_management.profile_readonly_emails' => ['readonly-lock@example.com'],
        'user_management.profile_readonly_names' => [],
    ]);

    $locked = User::factory()->create([
        'email_verified_at' => now(),
        'email' => 'readonly-lock@example.com',
        'module_access' => umAll(true),
    ]);

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => umAll(true),
    ]);

    $this->actingAs($admin)
        ->get(route('user-management.edit', $locked))
        ->assertForbidden();
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
