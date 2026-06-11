<?php

use App\Enums\CallEventStatus;
use App\Enums\ServiceVisibility;
use App\Models\CallEvent;
use App\Models\CallTrackingNumber;
use App\Models\MarketingClickEvent;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('exotel.enabled', true);
    Config::set('exotel.webhook_hmac_secret', 'test-exotel-secret');
    Config::set('marketing_attribution.enabled', true);
});

function exotelSignature(array $payload, string $secret): string
{
    return hash_hmac('sha256', json_encode($payload), $secret);
}

it('rejects exotel webhooks with invalid hmac signature', function () {
    $payload = ['CallSid' => 'abc123', 'Status' => 'completed'];

    $this->postJson('/api/integrations/exotel/webhook', $payload, [
        'X-Exotel-Signature' => 'invalid',
    ])->assertForbidden();
});

it('ingests exotel call events idempotently', function () {
    $payload = [
        'CallSid' => 'exotel-call-001',
        'EventType' => 'terminal',
        'Status' => 'completed',
        'DateUpdated' => '2026-06-11 10:00:00',
        'From' => '+919123456789',
        'To' => '+918000000000',
        'ConversationDuration' => 45,
        'Direction' => 'inbound',
    ];

    $signature = exotelSignature($payload, 'test-exotel-secret');

    $this->postJson('/api/integrations/exotel/webhook', $payload, [
        'X-Exotel-Signature' => $signature,
    ])->assertOk()->assertJson(['recorded' => true, 'duplicate' => false]);

    $this->postJson('/api/integrations/exotel/webhook', $payload, [
        'X-Exotel-Signature' => $signature,
    ])->assertOk()->assertJson(['recorded' => false, 'duplicate' => true]);

    expect(CallEvent::query()->count())->toBe(1);

    $event = CallEvent::query()->first();
    expect($event->status)->toBe(CallEventStatus::Completed)
        ->and($event->duration_seconds)->toBe(45)
        ->and($event->caller_normalized)->toBe('9123456789');
});

it('stitches call events to recent phone clicks', function () {
    CallTrackingNumber::query()->create([
        'provider' => 'exotel',
        'phone_number' => '+918000000000',
        'phone_normalized' => '8000000000',
        'is_active' => true,
        'is_primary' => true,
    ]);

    $service = Service::factory()->create([
        'service_code' => 'nursing',
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $this->get('/services/nursing?utm_source=google')->assertSuccessful();

    $this->postJson(route('marketing.track'), [
        'event_type' => 'phone_click',
        'page_path' => '/services/nursing',
        'session_fingerprint' => session()->getId(),
    ])->assertJson(['recorded' => true]);

    $click = MarketingClickEvent::query()->first();
    expect($click->service_id)->toBe($service->id);

    $payload = [
        'CallSid' => 'exotel-call-stitch',
        'EventType' => 'terminal',
        'Status' => 'completed',
        'DateUpdated' => now()->toDateTimeString(),
        'From' => '+919998887766',
        'To' => '+918000000000',
        'ConversationDuration' => 30,
        'CustomField' => (string) $click->marketing_attribution_session_id,
    ];

    $this->postJson('/api/integrations/exotel/webhook', $payload, [
        'X-Exotel-Signature' => exotelSignature($payload, 'test-exotel-secret'),
    ])->assertOk();

    $event = CallEvent::query()->where('provider_call_sid', 'exotel-call-stitch')->first();
    expect($event)->not->toBeNull()
        ->and($event->marketing_click_event_id)->toBe($click->id)
        ->and($event->service_id)->toBe($service->id);
});

it('maps missed and busy exotel statuses', function () {
    $missed = [
        'CallSid' => 'missed-1',
        'EventType' => 'terminal',
        'Status' => 'no-answer',
        'DateUpdated' => now()->toDateTimeString(),
    ];

    $this->postJson('/api/integrations/exotel/webhook', $missed, [
        'X-Exotel-Signature' => exotelSignature($missed, 'test-exotel-secret'),
    ])->assertOk();

    expect(CallEvent::query()->where('provider_call_sid', 'missed-1')->value('status'))
        ->toBe(CallEventStatus::Missed);

    $busy = [
        'CallSid' => 'busy-1',
        'EventType' => 'terminal',
        'Status' => 'busy',
        'DateUpdated' => now()->toDateTimeString(),
    ];

    $this->postJson('/api/integrations/exotel/webhook', $busy, [
        'X-Exotel-Signature' => exotelSignature($busy, 'test-exotel-secret'),
    ])->assertOk();

    expect(CallEvent::query()->where('provider_call_sid', 'busy-1')->value('status'))
        ->toBe(CallEventStatus::Busy);
});
