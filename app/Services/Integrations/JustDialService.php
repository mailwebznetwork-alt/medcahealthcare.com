<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JustDialService
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function testConnection(Integration $integration): array
    {
        try {
            if (! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Integration disabled.', 'data' => []];
            }

            $apiKey = (string) $integration->getCredential('api_key');
            $profileId = (string) $integration->getCredential('profile_id');
            $endpointUrl = (string) ($integration->getCredential('endpoint_url') ?: 'https://api.justdial.com');

            if ($apiKey === '' || $profileId === '') {
                return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
            }

            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'X-Profile-Id' => $profileId,
                ])
                ->get(rtrim($endpointUrl, '/').'/health');

            if (! $response->successful()) {
                $this->activityLogService->log('integration_test_failure', 'integrations', 'Just Dial test failed.');

                return ['success' => false, 'message' => 'Just Dial connection failed.', 'data' => ['status' => $response->status()]];
            }

            $integration->forceFill(['last_used_at' => now()])->save();
            $this->activityLogService->log('integration_test_success', 'integrations', 'Just Dial test passed.');

            return ['success' => true, 'message' => 'Just Dial connection successful.', 'data' => ['status' => $response->status()]];
        } catch (\Throwable $exception) {
            Log::error('Just Dial test failed.', ['error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_test_failure', 'integrations', 'Just Dial test failed.');

            return ['success' => false, 'message' => 'Just Dial test failed.', 'data' => []];
        }
    }
}
