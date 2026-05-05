<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaService
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function testConnection(Integration $integration): array
    {
        try {
            if (! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Integration disabled.', 'data' => []];
            }

            $pixelField = $integration->name === 'meta_capi' ? 'capi_pixel_id' : 'pixel_id';
            $tokenField = $integration->name === 'meta_capi' ? 'capi_access_token' : 'access_token';
            $pixelId = (string) $integration->getCredential($pixelField);
            $accessToken = (string) $integration->getCredential($tokenField);

            if ($pixelId === '' || $accessToken === '') {
                return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
            }

            $url = $integration->name === 'meta_capi'
                ? "https://graph.facebook.com/v20.0/{$pixelId}/events"
                : "https://graph.facebook.com/v20.0/{$pixelId}";

            $payload = $integration->name === 'meta_capi'
                ? ['data' => [['event_name' => 'Lead', 'event_time' => time(), 'action_source' => 'website']]]
                : ['fields' => 'id,name'];

            if ($integration->name === 'meta_capi') {
                $testEventCode = (string) ($integration->getCredential('test_event_code') ?? '');
                if ($testEventCode !== '') {
                    $payload['test_event_code'] = $testEventCode;
                }
            }

            $http = Http::timeout(8)->connectTimeout(5);
            $response = $integration->name === 'meta_capi'
                ? $http->post($url, $payload + ['access_token' => $accessToken])
                : $http->get($url, $payload + ['access_token' => $accessToken]);

            if (! $response->successful()) {
                $this->activityLogService->log('integration_test_failure', 'integrations', 'Meta integration test failed.');

                return ['success' => false, 'message' => 'Meta connection failed.', 'data' => ['status' => $response->status()]];
            }

            $integration->forceFill(['last_used_at' => now()])->save();
            $this->activityLogService->log('integration_test_success', 'integrations', 'Meta integration test passed.');

            return ['success' => true, 'message' => 'Meta connection successful.', 'data' => ['status' => $response->status()]];
        } catch (\Throwable $exception) {
            Log::error('Meta integration test failed.', ['error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_test_failure', 'integrations', 'Meta integration test failed.');

            return ['success' => false, 'message' => 'Meta integration test failed.', 'data' => []];
        }
    }
}
