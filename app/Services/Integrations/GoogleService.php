<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Log;

class GoogleService
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function testConnection(Integration $integration): array
    {
        try {
            if (! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Integration disabled.', 'data' => []];
            }

            $credentials = $integration->credentials;
            $requiredByIntegration = [
                'google_analytics' => ['measurement_id', 'api_key'],
                'google_ads' => ['google_ads_aw_id', 'api_key'],
                'google_tag_manager' => ['container_id', 'verification_code'],
            ];
            $required = $requiredByIntegration[$integration->name] ?? [];

            foreach ($required as $field) {
                if (! is_array($credentials) || ! filled($credentials[$field] ?? null)) {
                    return ['success' => false, 'message' => 'Missing required credentials.', 'data' => ['missing' => $field]];
                }
            }

            $integration->forceFill(['last_used_at' => now()])->save();
            $this->activityLogService->log('integration_test_success', 'integrations', 'Google integration test passed.');

            return ['success' => true, 'message' => 'Google configuration is valid.', 'data' => []];
        } catch (\Throwable $exception) {
            Log::error('Google integration test failed.', ['error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_test_failure', 'integrations', 'Google integration test failed.');

            return ['success' => false, 'message' => 'Google integration test failed.', 'data' => []];
        }
    }
}
