<?php

use App\Models\Integration;
use App\Models\MarketingClickEvent;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Integrations\CredentialVault;
use App\Services\Integrations\WhatsAppClickToChatService;
use App\Support\WhatsAppClickNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('builds wa me urls with optional default message', function () {
    $number = WhatsAppClickNumber::fromArray([
        'display_name' => 'Bookings',
        'phone' => '+91 88849 99002',
        'default_message' => 'Hello Medca',
        'enabled' => true,
        'sort_order' => 1,
    ]);

    expect($number)->not->toBeNull()
        ->and($number->waMeUrl())->toBe('https://wa.me/918884999002?text='.rawurlencode('Hello Medca'));
});

it('loads click numbers from whatsapp integration credentials', function () {
    if (! Schema::hasTable('integrations')) {
        $this->markTestSkipped();
    }

    $vault = app(CredentialVault::class);
    Integration::query()->create([
        'name' => WhatsAppClickToChatService::INTEGRATION_NAME,
        'type' => 'communication',
        'is_enabled' => true,
        'credentials' => $vault->encrypt([
            'floating_button_enabled' => true,
            'click_numbers' => [
                [
                    'display_name' => 'Admissions',
                    'phone' => '919999999999',
                    'default_message' => 'Hi',
                    'enabled' => true,
                    'sort_order' => 1,
                ],
            ],
        ]),
    ]);

    $active = app(WhatsAppClickToChatService::class)->activeNumbers();

    expect($active)->toHaveCount(1)
        ->and($active[0]->displayName)->toBe('Admissions');
});

it('preserves whatsapp business multi account storage', function () {
    if (! Schema::hasTable('integrations') || ! Schema::hasTable('integration_accounts')) {
        $this->markTestSkipped();
    }

    $integration = Integration::query()->create([
        'name' => WhatsAppClickToChatService::BUSINESS_API_INTEGRATION_NAME,
        'type' => 'communication',
        'is_enabled' => true,
        'credentials' => [],
    ]);

    $integration->accounts()->create([
        'label' => 'API Line 1',
        'account_identifier' => '12345',
        'credentials' => app(CredentialVault::class)->encrypt([
            'phone_number_id' => '12345',
            'access_token' => 'token',
            'webhook_verify_token' => 'verify',
        ]),
        'is_enabled' => true,
    ]);

    expect($integration->accounts)->toHaveCount(1);
});

it('records whatsapp click with phone and button metadata', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->postJson(route('marketing.track'), [
            'event_type' => 'whatsapp_click',
            'button_name' => 'Customer Care',
            'phone_number' => '918884999002',
            'page_path' => '/contact',
            'source' => 'google',
            'medium' => 'cpc',
            'campaign' => 'spring',
        ])
        ->assertJsonPath('recorded', true);

    $event = MarketingClickEvent::query()->latest('id')->first();
    expect($event)->not->toBeNull()
        ->and($event->element_label)->toBe('Customer Care')
        ->and($event->meta['phone_number'] ?? null)->toBe('918884999002');
});

it('saves up to five whatsapp click numbers via admin endpoint', function () {
    if (! Schema::hasTable('integrations')) {
        $this->markTestSkipped();
    }

    Integration::query()->create([
        'name' => WhatsAppClickToChatService::INTEGRATION_NAME,
        'type' => 'communication',
        'is_enabled' => false,
        'credentials' => [],
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
        'email_verified_at' => now(),
        'module_access' => array_fill_keys(ModuleAccess::keys(), true),
    ]);

    $numbers = [];
    for ($i = 1; $i <= 5; $i++) {
        $numbers[] = [
            'display_name' => "Dept {$i}",
            'phone' => '91999999999'.$i,
            'default_message' => 'Hello',
            'enabled' => '1',
            'sort_order' => $i,
        ];
    }

    $this->actingAs($admin)
        ->post(route('admin.settings.integrations.whatsapp.click-to-chat'), [
            'is_enabled' => '1',
            'floating_button_enabled' => '1',
            'click_numbers' => $numbers,
        ])
        ->assertRedirect(route('settings.integrations'));

    $stored = app(WhatsAppClickToChatService::class)->configuredNumbers();
    expect($stored)->toHaveCount(5);
});
