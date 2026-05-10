<?php

it('returns service unavailable when payment ingest is not configured', function () {
    config([
        'settings.payment_ingest_bearer' => null,
        'settings.payment_ingest_hmac_secret' => null,
    ]);

    $this->postJson('/api/payments/notify', [
        'amount' => 100,
    ])->assertStatus(503);
});

it('accepts bearer-only ingest when HMAC is not configured', function () {
    config([
        'settings.payment_ingest_bearer' => 'test-bearer-token',
        'settings.payment_ingest_hmac_secret' => null,
    ]);

    $this->withHeaders([
        'Authorization' => 'Bearer test-bearer-token',
    ])->postJson('/api/payments/notify', [
        'amount' => 50,
        'currency' => 'INR',
    ])->assertOk()->assertJson(['ok' => true]);
});

it('accepts HMAC-signed payloads when secret is configured', function () {
    config([
        'settings.payment_ingest_hmac_secret' => 'super-secret-hmac',
        'settings.payment_ingest_bearer' => null,
    ]);

    $payload = json_encode([
        'amount' => 25,
        'currency' => 'INR',
    ], JSON_THROW_ON_ERROR);

    $signature = hash_hmac('sha256', $payload, 'super-secret-hmac');

    $this->call(
        'POST',
        '/api/payments/notify',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_PAYMENT_SIGNATURE' => $signature,
        ],
        $payload
    )->assertOk()->assertJson(['ok' => true]);
});
