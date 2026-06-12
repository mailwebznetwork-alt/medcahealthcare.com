<?php

use App\Models\Integration;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Integrations\CredentialVault;
use Illuminate\Support\Facades\Schema;

function integrationsAllModulesOn(): array
{
    return collect(ModuleAccess::keys())
        ->mapWithKeys(fn (string $key) => [$key => true])
        ->all();
}

it('renders integrations HTML when GET admin integrations index without JSON accept header', function () {
    if (! Schema::hasTable('integrations')) {
        $this->markTestSkipped('Integrations table is not migrated.');
    }

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => integrationsAllModulesOn(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.settings.integrations.index'))
        ->assertOk()
        ->assertSee('Integrations');
});

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
        ->assertForbidden();
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

it('saves google business profile credentials via html form and redisplays account fields', function () {
    if (! Schema::hasTable('integrations')) {
        $this->markTestSkipped('Integrations table is not migrated.');
    }

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => integrationsAllModulesOn(),
        'role' => 'super_admin',
    ]);

    Integration::query()->updateOrCreate(
        ['name' => 'google_business_profile'],
        ['type' => 'google', 'credentials' => [], 'is_enabled' => false]
    );

    $this->actingAs($admin)
        ->post(route('admin.settings.integrations.update', ['name' => 'google_business_profile']), [
            'is_enabled' => '1',
            'credentials' => [
                'account_id' => 'accounts/123456789',
                'location_id' => 'locations/987654321',
                'oauth_refresh_token' => 'refresh-token-abc',
            ],
        ])
        ->assertRedirect(route('settings.integrations'))
        ->assertSessionHas('status');

    $decrypted = app(CredentialVault::class)->decrypt(
        Integration::query()->where('name', 'google_business_profile')->value('credentials')
    );

    expect($decrypted)->toMatchArray([
        'account_id' => 'accounts/123456789',
        'location_id' => 'locations/987654321',
        'oauth_refresh_token' => 'refresh-token-abc',
    ]);

    $this->actingAs($admin)
        ->get(route('settings.integrations'))
        ->assertOk()
        ->assertSee('accounts/123456789', false)
        ->assertSee('locations/987654321', false)
        ->assertSee(__('Current value saved.'), false);
});

it('blocks enabling integrations until required credentials are saved', function () {
    if (! Schema::hasTable('integrations')) {
        $this->markTestSkipped('Integrations table is not migrated.');
    }

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => integrationsAllModulesOn(),
        'role' => 'super_admin',
    ]);

    Integration::query()->updateOrCreate(
        ['name' => 'google_business_profile'],
        ['type' => 'google', 'credentials' => [], 'is_enabled' => false]
    );

    $this->actingAs($admin)
        ->patch(route('admin.settings.integrations.toggle', ['name' => 'google_business_profile']))
        ->assertRedirect(route('settings.integrations'))
        ->assertSessionHasErrors('integration');

    expect(Integration::query()->where('name', 'google_business_profile')->value('is_enabled'))->toBeFalse();
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
