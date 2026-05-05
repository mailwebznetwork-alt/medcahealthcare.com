<?php

use App\Models\Integration;
use App\Models\User;
use App\ModuleAccess;
use Illuminate\Support\Facades\Schema;

function integrationsAllModulesOn(): array
{
    return collect(ModuleAccess::keys())
        ->mapWithKeys(fn (string $key) => [$key => true])
        ->all();
}

it('lists integrations for admin users', function () {
    if (! Schema::hasTable('integrations')) {
        $this->markTestSkipped('Integrations table is not migrated.');
    }

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => integrationsAllModulesOn(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.settings.integrations.index'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Integrations fetched successfully.')
        ->assertJsonFragment(['name' => 'just_dial'])
        ->assertJsonFragment(['name' => 'youtube'])
        ->assertJsonFragment(['name' => 'linkedin'])
        ->assertJsonFragment(['name' => 'facebook'])
        ->assertJsonFragment(['name' => 'instagram']);
});

it('denies integrations access for non-admin users', function () {
    if (! Schema::hasTable('integrations')) {
        $this->markTestSkipped('Integrations table is not migrated.');
    }

    $viewer = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => integrationsAllModulesOn(),
        'role' => 'viewer',
    ]);

    $this->actingAs($viewer)
        ->getJson(route('admin.settings.integrations.index'))
        ->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Forbidden.');
});

it('updates and masks credentials for chatgpt integration', function () {
    if (! Schema::hasTable('integrations')) {
        $this->markTestSkipped('Integrations table is not migrated.');
    }

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => integrationsAllModulesOn(),
        'role' => 'super_admin',
    ]);

    Integration::query()->updateOrCreate(
        ['name' => 'chatgpt'],
        ['type' => 'ai', 'credentials' => [], 'is_enabled' => false]
    );

    $this->actingAs($admin)
        ->postJson(route('admin.settings.integrations.update', ['name' => 'chatgpt']), [
            'is_enabled' => true,
            'credentials' => [
                'api_key' => 'sk-test-1234567890',
                'model' => 'gpt-4o-mini',
                'temperature' => 0.3,
            ],
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.is_enabled', true);

    $this->actingAs($admin)
        ->getJson(route('admin.settings.integrations.show', ['name' => 'chatgpt']))
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('retains existing meta capi access token when blank is submitted', function () {
    if (! Schema::hasTable('integrations')) {
        $this->markTestSkipped('Integrations table is not migrated.');
    }

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => integrationsAllModulesOn(),
        'role' => 'super_admin',
    ]);

    $integration = Integration::query()->updateOrCreate(
        ['name' => 'meta_capi'],
        [
            'type' => 'meta',
            'credentials' => [
                'capi_pixel_id' => '9999999999',
                'capi_access_token' => 'existing-meta-token',
                'test_event_code' => 'TEST001',
            ],
            'is_enabled' => true,
        ]
    );

    $this->actingAs($admin)
        ->postJson(route('admin.settings.integrations.update', ['name' => 'meta_capi']), [
            'is_enabled' => true,
            'credentials' => [
                'capi_pixel_id' => '9999999999',
                'capi_access_token' => '',
                'test_event_code' => 'TEST002',
            ],
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($integration->fresh()?->getCredential('capi_access_token'))->toBe('existing-meta-token');
});
