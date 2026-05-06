<?php

use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

it('sends an outbound webhook when a lead is created and the webhook integration is enabled', function () {
    if (! Schema::hasTable('integrations')) {
        $this->markTestSkipped('Integrations table is not migrated.');
    }

    Http::fake([
        'https://receiver.example/hooks/mm' => Http::response(['ok' => true], 200),
    ]);

    Integration::query()->updateOrCreate(
        ['name' => 'webhook'],
        [
            'type' => 'automation',
            'credentials' => [
                'endpoint_url' => 'https://receiver.example/hooks/mm',
                'secret' => 'whsec_test',
            ],
            'is_enabled' => true,
        ]
    );

    $this->postJson('/api/leads', [
        'name' => 'Test User',
        'phone' => '9876543210',
        'service' => 'Home care',
    ])->assertCreated();

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://receiver.example/hooks/mm'
            && str_contains((string) $request->body(), 'lead.created')
            && $request->hasHeader('X-Webhook-Event', 'lead.created');
    });
});
