<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

/**
 * PDF Settings §4–6 — outbound HTTP notifications for automation integrations (integration name: webhook).
 */
class OutboundWebhookDispatcher
{
    /**
     * POST JSON payload to the configured webhook receiver when enabled.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $eventKey, array $payload): void
    {
        $integration = Integration::query()
            ->where('name', 'webhook')
            ->where('is_enabled', true)
            ->first();

        if ($integration === null) {
            return;
        }

        $url = $integration->getCredential('endpoint_url');
        if (! is_string($url) || trim($url) === '') {
            return;
        }

        $secret = $integration->getCredential('secret');

        $body = [
            'event' => $eventKey,
            'payload' => $payload,
            'sent_at' => now()->toIso8601String(),
            'app' => config('app.name'),
            'environment' => config('app.env'),
        ];

        try {
            $json = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            Log::notice('Outbound webhook JSON encode failed', ['event' => $eventKey]);

            return;
        }

        $signature = is_string($secret) && $secret !== ''
            ? hash_hmac('sha256', $json, $secret)
            : '';

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Webhook-Event' => $eventKey,
        ];
        if ($signature !== '') {
            $headers['X-Webhook-Signature'] = $signature;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders($headers)
                ->withBody($json, 'application/json')
                ->post($url);

            if (! $response->successful()) {
                Log::notice('Outbound webhook HTTP response not successful', [
                    'event' => $eventKey,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $e) {
            Log::notice('Outbound webhook request failed', [
                'event' => $eventKey,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
