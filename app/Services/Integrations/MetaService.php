<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaService
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function testConnection(): array
    {
        try {
            $integration = Integration::query()->where('name', 'meta_ads')->first();

            if (! $integration instanceof Integration || ! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Integration disabled.', 'data' => []];
            }

            $pixelId = (string) $integration->getCredential('pixel_id');
            $accessToken = (string) $integration->getCredential('access_token');

            if ($pixelId === '' || $accessToken === '') {
                return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
            }

            $response = Http::timeout(8)
                ->connectTimeout(5)
                ->get("https://graph.facebook.com/v20.0/{$pixelId}", [
                    'access_token' => $accessToken,
                    'fields' => 'id,name',
                ]);

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

    public function testConversionsApi(): array
    {
        try {
            $integration = Integration::query()->where('name', 'meta_capi')->first();

            if (! $integration instanceof Integration || ! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Integration disabled.', 'data' => []];
            }

            $pixelId = (string) $integration->getCredential('capi_pixel_id');
            $accessToken = (string) $integration->getCredential('capi_access_token');
            $testEventCode = (string) ($integration->getCredential('test_event_code') ?? '');

            if ($pixelId === '' || $accessToken === '') {
                return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
            }

            $payload = [
                'data' => [
                    [
                        'event_name' => 'Lead',
                        'event_time' => time(),
                        'action_source' => 'website',
                    ],
                ],
            ];
            if ($testEventCode !== '') {
                $payload['test_event_code'] = $testEventCode;
            }

            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->post("https://graph.facebook.com/v20.0/{$pixelId}/events", $payload + [
                    'access_token' => $accessToken,
                ]);

            if (! $response->successful()) {
                $this->activityLogService->log('integration_test_failure', 'integrations', 'Meta CAPI test failed.');

                return ['success' => false, 'message' => 'Meta CAPI connection failed.', 'data' => ['status' => $response->status()]];
            }

            $integration->forceFill(['last_used_at' => now()])->save();
            $this->activityLogService->log('integration_test_success', 'integrations', 'Meta CAPI test passed.');

            return ['success' => true, 'message' => 'Meta CAPI connection successful.', 'data' => ['status' => $response->status()]];
        } catch (\Throwable $exception) {
            Log::error('Meta CAPI test failed.', ['error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_test_failure', 'integrations', 'Meta CAPI test failed.');

            return ['success' => false, 'message' => 'Meta CAPI test failed.', 'data' => []];
        }
    }
}
