<?php

use App\Models\Lead;

test('public lead form creates lead with csrf', function () {
    $response = $this->from('/contact')
        ->post(route('public.leads.store'), [
            'name' => 'Public Form User',
            'phone' => '9876512345',
            'service' => 'Home Nursing',
            'message' => 'Need visit',
            'submission_context' => 'contact_form',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('lead_status');

    expect(Lead::query()->where('phone', '9876512345')->exists())->toBeTrue();
});

test('public lead api path still works with api key', function () {
    config(['security.lead_api_key' => 'test-lead-api-key']);

    $this->postJson('/api/leads', [
        'name' => 'API User',
        'phone' => '9876599999',
        'service' => 'Consultation',
    ], ['X-API-KEY' => 'test-lead-api-key'])
        ->assertCreated();
});
