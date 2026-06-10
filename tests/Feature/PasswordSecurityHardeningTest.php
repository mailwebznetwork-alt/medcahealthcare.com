<?php

use App\Models\User;
use App\ModuleAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

it('requires current password on profile password update', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $response = $this->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword12',
            'password_confirmation' => 'NewPassword12',
        ]);

    $response->assertRedirect('/profile')
        ->assertSessionHasErrors();

    expect(DB::table('activity_logs')->where('action', 'password_change_failed')->exists())->toBeTrue();
});

it('rejects user management password reset without admin password confirmation', function () {
    $admin = User::factory()->create([
        'role' => 'super_admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);
    $target = User::factory()->create(['role' => 'editor']);

    $this->actingAs($admin)
        ->from(route('user-management.edit', $target))
        ->put(route('user-management.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'editor',
            'password' => 'NewPassword12',
            'password_confirmation' => 'NewPassword12',
        ])
        ->assertSessionHasErrors('admin_password');
});

it('blocks mass assignment of role and password on profile update', function () {
    $user = User::factory()->create([
        'role' => 'viewer',
        'password' => 'password',
    ]);

    $this->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => 'Updated Name',
            'email' => $user->email,
            'password' => 'HackedPass12',
            'role' => 'super_admin',
            'is_active' => false,
        ])
        ->assertRedirect('/profile');

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->role)->toBe('viewer')
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

it('exposes strict panel access helpers on the user model', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);
    $admin = User::factory()->create(['role' => 'admin']);
    $inactive = User::factory()->create(['role' => 'admin', 'is_active' => false]);

    expect($viewer->canAccessPanel())->toBeTrue()
        ->and($viewer->canAccessIntegrationsAdmin())->toBeFalse()
        ->and($admin->canAccessIntegrationsAdmin())->toBeTrue()
        ->and($inactive->canAccessPanel())->toBeFalse();
});
