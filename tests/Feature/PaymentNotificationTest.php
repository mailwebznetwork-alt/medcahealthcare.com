<?php

use Illuminate\Support\Facades\Config;

it('accepts payment notify with configured bearer token', function () {
    Config::set('settings.payment_ingest_bearer', 'test-bearer-secret');

    $this->postJson('/api/payments/notify', [
        'amount' => 99.5,
        'currency' => 'INR',
        'reference' => 'ord_1',
    ], [
        'Authorization' => 'Bearer test-bearer-secret',
    ])->assertOk()
        ->assertJson(['ok' => true]);
});

it('rejects payment notify without valid bearer', function () {
    Config::set('settings.payment_ingest_bearer', 'test-bearer-secret');

    $this->postJson('/api/payments/notify', [
        'amount' => 10,
    ], [
        'Authorization' => 'Bearer wrong',
    ])->assertForbidden();
});
