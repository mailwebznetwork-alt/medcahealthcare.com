<?php

use App\Models\Lead;

test('post api leads creates organic lead when source omitted', function () {
    $response = $this->postJson('/api/leads', [
        'name' => 'Test Patient',
        'phone' => '9876543210',
        'service' => 'Consultation',
        'message' => 'Hello',
    ]);

    $response->assertCreated()
        ->assertJsonPath('duplicate', false)
        ->assertJsonStructure(['data' => ['uuid', 'id']]);

    $lead = Lead::query()->first();
    expect($lead)->not->toBeNull()
        ->and($lead->source->value)->toBe('organic');
});

test('post api leads maps utm to source and campaign', function () {
    $response = $this->postJson('/api/leads', [
        'name' => 'Utm User',
        'phone' => '9123456780',
        'service' => 'Home visit',
        'utm_source' => 'facebook',
        'utm_campaign' => 'monsoon_2026',
    ]);

    $response->assertCreated();
    $lead = Lead::query()->where('phone', '9123456780')->first();
    expect($lead->source->value)->toBe('meta_ads')
        ->and($lead->campaign)->toBe('monsoon_2026');
});

test('post api leads returns duplicate for rapid same phone and service', function () {
    $this->postJson('/api/leads', [
        'name' => 'Dup',
        'phone' => '9000000001',
        'service' => 'Lab test',
    ])->assertCreated();

    $response = $this->postJson('/api/leads', [
        'name' => 'Dup Again',
        'phone' => '9000000001',
        'service' => 'Lab test',
    ]);

    $response->assertOk()
        ->assertJsonPath('duplicate', true);
});
