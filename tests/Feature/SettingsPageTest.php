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
        ->get(route('settings.index'))
        ->assertOk()
        ->assertSee('Integrations')
        ->assertSee('Outbound webhook events')
        ->assertSee('openai', false);
});
