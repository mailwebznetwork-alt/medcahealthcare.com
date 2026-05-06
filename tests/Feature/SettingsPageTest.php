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

it('redirects legacy settings URL to integrations', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => settingsAllModulesOn(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->get(route('settings.index'))
        ->assertRedirect(route('settings.integrations'));
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
        ->assertDontSee('Outbound webhook events');
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
        ->assertSee('Outbound webhook events')
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

it('allows backup and maintenance pages for super admins', function () {
    $super = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => settingsAllModulesOn(),
        'role' => 'super_admin',
    ]);

    $this->actingAs($super)
        ->get(route('settings.backup'))
        ->assertOk()
        ->assertSee('Database backup');

    $this->actingAs($super)
        ->get(route('settings.maintenance'))
        ->assertOk()
        ->assertSee('Maintenance mode');
});
