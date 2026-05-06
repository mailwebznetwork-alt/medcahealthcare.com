<?php

namespace App\Services\Webhooks;

use App\Models\OutboundWebhook;
use App\Models\WebhookDelivery;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class OutboundWebhookSender
{
    public function __construct(
        private readonly WebhookPayloadBuilder $payloadBuilder,
        private readonly WebhookPayloadMapper $payloadMapper,
        private readonly WebhookDestinationGuard $destinationGuard
    ) {}

    /**
     * Deliver webhook with retries and per-attempt logs.
     *
     * @param  array<string, mixed>  $payload
     */
    public function send(OutboundWebhook $webhook, string $eventKey, array $payload): void
    {
        if (! $webhook->is_enabled) {
            return;
        }

        $payload = $this->payloadMapper->apply($payload, $webhook->mapping_rules, $eventKey);

        $url = $webhook->target_url;
        if ($webhook->enforce_https && ! Str::startsWith(strtolower($url), 'https://')) {
            $this->writeDeliveryLog(
                $webhook,
                $eventKey,
                1,
                null,
                null,
                null,
                null,
                false,
                'HTTPS is required for this endpoint.',
                0
            );

            return;
        }

        $cidrs = $webhook->allowed_destination_cidrs ?? [];
        if (is_array($cidrs) && $cidrs !== [] && ! $this->destinationGuard->isHostAllowed($url, array_values(array_filter(array_map('strval', $cidrs))))) {
            $this->writeDeliveryLog(
                $webhook,
                $eventKey,
                1,
                null,
                null,
                null,
                null,
                false,
                'Destination host IP is not in the configured allowlist.',
                0
            );

            return;
        }

        $method = strtoupper($webhook->http_method);
        $max = max(1, min(10, (int) $webhook->max_retries));
        $timeout = max(1, min(120, (int) $webhook->timeout_seconds));
        $verifySsl = (bool) $webhook->verify_ssl;

        for ($attempt = 1; $attempt <= $max; $attempt++) {
            $encodedBody = null;
            $started = microtime(true);

            try {
                if ($method === 'GET') {
                    $response = $this->sendGet($webhook, $eventKey, $payload, $timeout, $verifySsl);
                    $requestFull = json_encode([
                        'method' => 'GET',
                        'url' => $url,
                        'event' => $eventKey,
                        'sent_at' => now()->toIso8601String(),
                    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                    $responseFull = $response->body();
                } else {
                    $encodedBody = $this->payloadBuilder->bodyJson($webhook, $eventKey, $payload);
                    $response = $this->sendWithBody($webhook, $eventKey, $encodedBody, $method, $timeout, $verifySsl);
                    $requestFull = $encodedBody;
                    $responseFull = $response->body();
                }

                $durationMs = (int) round((microtime(true) - $started) * 1000);
                $summary = $method === 'GET'
                    ? 'GET '.Str::limit($url, 500)
                    : Str::limit($method.' '.($encodedBody ?? ''), 4000);

                if ($response->successful()) {
                    $this->writeDeliveryLog(
                        $webhook,
                        $eventKey,
                        $attempt,
                        $summary,
                        $requestFull,
                        $response->status(),
                        $responseFull,
                        true,
                        null,
                        $durationMs
                    );

                    return;
                }

                $this->writeDeliveryLog(
                    $webhook,
                    $eventKey,
                    $attempt,
                    $summary,
                    $requestFull,
                    $response->status(),
                    $responseFull,
                    false,
                    'HTTP '.$response->status(),
                    $durationMs
                );
            } catch (JsonException $e) {
                $durationMs = (int) round((microtime(true) - $started) * 1000);
                $this->writeDeliveryLog(
                    $webhook,
                    $eventKey,
                    $attempt,
                    null,
                    $encodedBody,
                    null,
                    null,
                    false,
                    'Invalid payload template: '.$e->getMessage(),
                    $durationMs
                );

                return;
            } catch (Throwable $e) {
                $durationMs = (int) round((microtime(true) - $started) * 1000);
                $this->writeDeliveryLog(
                    $webhook,
                    $eventKey,
                    $attempt,
                    null,
                    $encodedBody ?? null,
                    null,
                    null,
                    false,
                    $e->getMessage(),
                    $durationMs
                );
            }

            if ($attempt < $max) {
                usleep(250_000);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendGet(
        OutboundWebhook $webhook,
        string $eventKey,
        array $payload,
        int $timeout,
        bool $verifySsl
    ): Response {
        $query = [
            'event' => $eventKey,
            'sent_at' => now()->toIso8601String(),
        ];

        $secret = $webhook->secret;
        if (is_string($secret) && $secret !== '') {
            $canonical = $eventKey.'|'.$query['sent_at'];
            $query['signature'] = hash_hmac('sha256', $canonical, $secret);
        }

        $pending = Http::timeout($timeout)
            ->withOptions(['verify' => $verifySsl])
            ->withHeaders($this->baseHeaders($webhook, $eventKey, null));

        return $pending->get($webhook->target_url, $query);
    }

    private function sendWithBody(
        OutboundWebhook $webhook,
        string $eventKey,
        string $body,
        string $method,
        int $timeout,
        bool $verifySsl
    ): Response {
        $headers = $this->baseHeaders($webhook, $eventKey, $body);

        $pending = Http::timeout($timeout)
            ->withOptions(['verify' => $verifySsl])
            ->withHeaders($headers);

        return match ($method) {
            'POST' => $pending->withBody($body, 'application/json')->post($webhook->target_url),
            'PUT' => $pending->withBody($body, 'application/json')->put($webhook->target_url),
            'PATCH' => $pending->withBody($body, 'application/json')->patch($webhook->target_url),
            default => $pending->withBody($body, 'application/json')->post($webhook->target_url),
        };
    }

    /**
     * @return array<string, string>
     */
    private function baseHeaders(OutboundWebhook $webhook, string $eventKey, ?string $jsonBody): array
    {
        $headers = array_merge(
            [
                'Accept' => 'application/json',
                'X-Webhook-Event' => $eventKey,
            ],
            $webhook->custom_headers ?? []
        );

        if ($jsonBody !== null) {
            $headers['Content-Type'] = 'application/json';
            $headers['X-Webhook-Body-Sha256'] = hash('sha256', $jsonBody);
            $secret = $webhook->secret;
            if (is_string($secret) && $secret !== '') {
                $headers['X-Webhook-Signature'] = hash_hmac('sha256', $jsonBody, $secret);
            }
        }

        $token = $webhook->auth_bearer_token;
        if (is_string($token) && $token !== '') {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        return $headers;
    }

    private function writeDeliveryLog(
        OutboundWebhook $webhook,
        string $eventKey,
        int $attempt,
        ?string $summary,
        ?string $requestFull,
        ?int $responseStatus,
        ?string $responseFull,
        bool $success,
        ?string $errorMessage,
        int $durationMs
    ): void {
        $respShort = $responseFull !== null ? Str::limit($responseFull, 8000) : null;

        WebhookDelivery::query()->create([
            'outbound_webhook_id' => $webhook->id,
            'event_key' => $eventKey,
            'attempt_number' => $attempt,
            'request_summary' => $summary,
            'request_payload' => $requestFull,
            'response_status' => $responseStatus,
            'response_body' => $respShort,
            'response_payload' => $responseFull,
            'success' => $success,
            'error_message' => $errorMessage !== null ? Str::limit($errorMessage, 2000) : null,
            'duration_ms' => $durationMs,
        ]);
    }
}
