<?php

use App\Models\Integration;
use App\Models\User;
use App\ModuleAccess;

function settingsAllModulesOn(): array
{
    return collect(ModuleAccess::keys())
        ->mapWithKeys(fn (string $key) => [$key => true])
        ->all();
}

it('redirects legacy settings URL to appearance', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => settingsAllModulesOn(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->get(route('settings.index'))
        ->assertRedirect(route('settings.appearance'));
});

it('renders settings integrations page with integration cards', function () {
    Integration::query()->updateOrCreate(
        ['name' => 'openai'],
        ['type' => 'ai', 'credentials' => ['api_key' => 'sk-test-1234', 'model' => 'gpt-4o-mini', 'temperature' => 0.3], 'is_enabled' => true]
    );

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => settingsAllModulesOn(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->get(route('settings.integrations'))
        ->assertOk()
        ->assertSee('Integrations')
        ->assertDontSee('Webhook Manager');
});

it('renders webhooks settings page', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => settingsAllModulesOn(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->get(route('settings.webhooks'))
        ->assertOk()
        ->assertSee('Webhook Manager')
        ->assertSee('lead.created');
});

it('forbids backup and maintenance pages for non-super admins', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => settingsAllModulesOn(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->get(route('settings.backup'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('settings.maintenance'))
        ->assertForbidden();
});

it('forbids backup page for super admins who are not configured backup operators', function () {
    $super = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => settingsAllModulesOn(),
        'role' => 'super_admin',
        'name' => 'Other Admin',
    ]);

    $this->actingAs($super)
        ->get(route('settings.backup'))
        ->assertForbidden();
});

it('allows backup page only for configured backup operators', function () {
    $operator = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => settingsAllModulesOn(),
        'role' => 'super_admin',
        'name' => 'WDJERRIE',
    ]);

    $this->actingAs($operator)
        ->get(route('settings.backup'))
        ->assertOk()
        ->assertSee('Database backup')
        ->assertSee('Download full site backup')
        ->assertSee('Restore from backup zip');
});

it('allows maintenance page for any super admin', function () {
    $super = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => settingsAllModulesOn(),
        'role' => 'super_admin',
        'name' => 'Other Admin',
    ]);

    $this->actingAs($super)
        ->get(route('settings.maintenance'))
        ->assertOk()
        ->assertSee('Maintenance mode');
});
