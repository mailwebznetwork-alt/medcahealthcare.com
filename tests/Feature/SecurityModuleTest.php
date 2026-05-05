<?php

use App\Models\User;
use App\ModuleAccess;
use Illuminate\Support\Facades\DB;

function securityAllModulesOn(): array
{
    return collect(ModuleAccess::keys())
        ->mapWithKeys(fn (string $key) => [$key => true])
        ->all();
}

it('shows security metrics for admin role users', function () {
    DB::table('activity_logs')->insert([
        [
            'user_id' => null,
            'action' => 'login_failure',
            'module' => 'auth',
            'description' => 'Failed login test',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Pest',
            'created_at' => now(),
        ],
        [
            'user_id' => null,
            'action' => 'role_violation',
            'module' => 'rbac',
            'description' => 'Role denial test',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Pest',
            'created_at' => now(),
        ],
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => securityAllModulesOn(),
        'role' => 'admin',
    ]);

    $this->actingAs($user)
        ->get(route('modules.security'))
        ->assertOk()
        ->assertSee('Failed Logins')
        ->assertSee('Role Denials')
        ->assertSee('Failed Login Attempts by IP')
        ->assertSee('Recent Security Events');
});

it('denies access to security page for viewer role users', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => securityAllModulesOn(),
        'role' => 'viewer',
    ]);

    $this->actingAs($user)
        ->get(route('modules.security'))
        ->assertStatus(403)
        ->assertJson(['message' => 'Forbidden.']);
});
