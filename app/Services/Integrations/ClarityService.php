<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Log;

class ClarityService
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function testConnection(Integration $integration): array
    {
        try {
            if (! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Integration disabled.', 'data' => []];
            }

            $projectId = (string) $integration->getCredential('project_id');
            if ($projectId === '') {
                return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
            }

            $integration->forceFill(['last_used_at' => now()])->save();
            $this->activityLogService->log('integration_test_success', 'integrations', 'Microsoft Clarity test passed.');

            return ['success' => true, 'message' => 'Microsoft Clarity configuration is valid.', 'data' => []];
        } catch (\Throwable $exception) {
            Log::error('Microsoft Clarity test failed.', ['error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_test_failure', 'integrations', 'Microsoft Clarity test failed.');

            return ['success' => false, 'message' => 'Microsoft Clarity test failed.', 'data' => []];
        }
    }
}
