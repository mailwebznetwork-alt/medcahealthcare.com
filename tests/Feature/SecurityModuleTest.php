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
        ->assertSee('Firewall & edge posture')
        ->assertSee('Detailed audit rows')
        ->assertDontSee('Successful login for');
});

it('shows full audit trail to the root administrator only', function () {
    DB::table('activity_logs')->insert([
        'user_id' => null,
        'action' => 'login_success',
        'module' => 'auth',
        'description' => 'Successful login for root@secret.test.',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'created_at' => now(),
    ]);

    $root = User::factory()->rootSuperAdmin()->create([
        'email_verified_at' => now(),
        'module_access' => securityAllModulesOn(),
    ]);

    $this->actingAs($root)
        ->get(route('modules.security'))
        ->assertOk()
        ->assertSee('Audit trail preview')
        ->assertSee('Successful login for root@secret.test.');
});

it('hides detailed audit rows from non-root users with security access', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => securityAllModulesOn(),
        'role' => 'viewer',
    ]);

    $this->actingAs($user)
        ->get(route('modules.security'))
        ->assertOk()
        ->assertSee('Detailed audit rows')
        ->assertDontSee('Successful login for');
});

it('returns security workspace for viewer role when module access is granted', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => securityAllModulesOn(),
        'role' => 'viewer',
    ]);

    $this->actingAs($user)
        ->getJson(route('modules.security'))
        ->assertOk();
});
