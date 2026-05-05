<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StorageService
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function testConnection(Integration $integration): array
    {
        try {
            if (! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Integration disabled.', 'data' => []];
            }

            $isValid = match ($integration->name) {
                'aws_s3' => $this->validateAwsCredentials($integration),
                'cloudflare' => $this->validateCloudflareCredentials($integration),
                'google_drive' => $this->validateGoogleDriveCredentials($integration),
                'onedrive' => $this->validateOneDriveCredentials($integration),
                default => false,
            };

            if (! $isValid) {
                $this->activityLogService->log('integration_test_failure', 'integrations', 'Storage integration test failed.');

                return ['success' => false, 'message' => 'Storage integration test failed.', 'data' => []];
            }

            $integration->forceFill(['last_used_at' => now()])->save();

            $this->activityLogService->log('integration_test_success', 'integrations', 'Storage integration test passed.');

            return ['success' => true, 'message' => 'Storage integration is valid.', 'data' => []];
        } catch (\Throwable $exception) {
            Log::error('Storage integration test failed.', ['error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_test_failure', 'integrations', 'Storage integration test failed.');

            return ['success' => false, 'message' => 'Storage integration test failed.', 'data' => []];
        }
    }

    private function validateAwsCredentials(Integration $integration): bool
    {
        $key = (string) $integration->getCredential('key');
        $secret = (string) $integration->getCredential('secret');
        $region = (string) $integration->getCredential('region');
        $bucket = (string) $integration->getCredential('bucket');

        return $key !== '' && $secret !== '' && $region !== '' && $bucket !== '';
    }

    private function validateCloudflareCredentials(Integration $integration): bool
    {
        $apiToken = (string) $integration->getCredential('api_token');
        $zoneId = (string) $integration->getCredential('zone_id');

        if ($apiToken === '' || $zoneId === '') {
            return false;
        }

        $response = Http::timeout(8)
            ->connectTimeout(5)
            ->withToken($apiToken)
            ->get("https://api.cloudflare.com/client/v4/zones/{$zoneId}");

        return $response->successful();
    }

    private function validateGoogleDriveCredentials(Integration $integration): bool
    {
        return (string) $integration->getCredential('client_id') !== ''
            && (string) $integration->getCredential('client_secret') !== ''
            && (string) $integration->getCredential('refresh_token') !== '';
    }

    private function validateOneDriveCredentials(Integration $integration): bool
    {
        return (string) $integration->getCredential('client_id') !== ''
            && (string) $integration->getCredential('client_secret') !== ''
            && (string) $integration->getCredential('refresh_token') !== ''
            && (string) $integration->getCredential('tenant_id') !== '';
    }
}
