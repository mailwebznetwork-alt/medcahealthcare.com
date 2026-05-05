<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function testConnection(Integration $integration): array
    {
        try {
            if (! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Integration disabled.', 'data' => []];
            }

            $endpoint = (string) $integration->getCredential('endpoint_url');
            $secret = (string) $integration->getCredential('secret');

            if ($endpoint === '' || $secret === '') {
                return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
            }

            $response = Http::timeout(8)
                ->connectTimeout(5)
                ->withHeaders([
                    'X-Webhook-Secret' => $secret,
                    'X-Webhook-Event' => 'integration.test',
                ])
                ->post($endpoint, [
                    'event' => 'integration.test',
                    'timestamp' => now()->toIso8601String(),
                    'source' => 'markonminds',
                ]);

            if (! $response->successful()) {
                $this->activityLogService->log('integration_test_failure', 'integrations', 'Webhook integration test failed.');

                return ['success' => false, 'message' => 'Webhook connection failed.', 'data' => ['status' => $response->status()]];
            }

            $integration->forceFill(['last_used_at' => now()])->save();
            $this->activityLogService->log('integration_test_success', 'integrations', 'Webhook integration test passed.');

            return ['success' => true, 'message' => 'Webhook connection successful.', 'data' => ['status' => $response->status()]];
        } catch (\Throwable $exception) {
            Log::error('Webhook integration test failed.', ['error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_test_failure', 'integrations', 'Webhook integration test failed.');

            return ['success' => false, 'message' => 'Webhook integration test failed.', 'data' => []];
        }
    }
}
