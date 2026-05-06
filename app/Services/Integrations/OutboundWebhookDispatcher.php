<?php

namespace App\Services\Integrations;

use App\Jobs\SendOutboundWebhookJob;
use App\Models\Integration;
use App\Models\OutboundWebhook;
use App\Services\Webhooks\OutboundWebhookSender;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use JsonException;
use Throwable;

/**
 * Outbound HTTP notifications — Webhook Manager (PDF §4–6) plus legacy Integration entry "webhook".
 */
class OutboundWebhookDispatcher
{
    public function __construct(private readonly OutboundWebhookSender $webhookSender) {}

    /**
     * Dispatch domain events to configured outbound endpoints (Settings → Webhooks), then legacy integration when none match.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $eventKey, array $payload): void
    {
        if (Schema::hasTable('outbound_webhooks')) {
            $hooks = OutboundWebhook::query()
                ->where('is_enabled', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->filter(fn (OutboundWebhook $webhook): bool => in_array($eventKey, $webhook->events ?? [], true));

            foreach ($hooks as $webhook) {
                if (Config::boolean('settings.webhooks.async_dispatch', true)) {
                    SendOutboundWebhookJob::dispatch($webhook->id, $eventKey, $payload);
                } else {
                    $this->webhookSender->send($webhook, $eventKey, $payload);
                }
            }

            if ($hooks->isNotEmpty()) {
                return;
            }
        }

        $this->dispatchLegacyWebhookIntegration($eventKey, $payload);
    }

    /**
     * Legacy single endpoint via Settings → Integrations ("Webhook" integration).
     *
     * @param  array<string, mixed>  $payload
     */
    private function dispatchLegacyWebhookIntegration(string $eventKey, array $payload): void
    {
        if (! Schema::hasTable('integrations')) {
            return;
        }

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
