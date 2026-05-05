<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrmService
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function testConnection(string $integrationName): array
    {
        try {
            $integration = Integration::query()->where('name', $integrationName)->first();

            if (! $integration instanceof Integration || ! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Integration disabled.', 'data' => []];
            }

            return match ($integrationName) {
                'crm_hubspot' => $this->testHubSpot($integration),
                'crm_salesforce' => $this->testSalesforce($integration),
                'crm_zoho' => $this->testZoho($integration),
                'crm_custom_1', 'crm_custom_2', 'crm_custom_3' => $this->testCustomCrm($integration),
                default => ['success' => false, 'message' => 'Unsupported CRM integration.', 'data' => []],
            };
        } catch (\Throwable $exception) {
            Log::error('CRM integration test failed.', ['integration' => $integrationName, 'error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_test_failure', 'integrations', "CRM integration test failed for {$integrationName}.");

            return ['success' => false, 'message' => 'CRM integration test failed.', 'data' => []];
        }
    }

    private function testHubSpot(Integration $integration): array
    {
        $token = (string) $integration->getCredential('access_token');
        if ($token === '') {
            return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
        }

        $response = Http::withToken($token)
            ->timeout(8)
            ->connectTimeout(5)
            ->get('https://api.hubapi.com/crm/v3/objects/contacts', ['limit' => 1]);

        return $this->buildCrmResult($integration, $response->successful(), 'HubSpot', $response->status());
    }

    private function testSalesforce(Integration $integration): array
    {
        $instanceUrl = (string) $integration->getCredential('instance_url');
        $accessToken = (string) $integration->getCredential('access_token');

        if ($instanceUrl === '' || $accessToken === '') {
            return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
        }

        $url = rtrim($instanceUrl, '/').'/services/data/v60.0/limits';
        $response = Http::withToken($accessToken)
            ->timeout(8)
            ->connectTimeout(5)
            ->get($url);

        return $this->buildCrmResult($integration, $response->successful(), 'Salesforce', $response->status());
    }

    private function testZoho(Integration $integration): array
    {
        $token = (string) $integration->getCredential('access_token');
        if ($token === '') {
            return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken '.$token,
        ])->timeout(8)
            ->connectTimeout(5)
            ->get('https://www.zohoapis.com/crm/v2/org');

        return $this->buildCrmResult($integration, $response->successful(), 'Zoho', $response->status());
    }

    private function buildCrmResult(Integration $integration, bool $success, string $provider, int $status): array
    {
        if (! $success) {
            $this->activityLogService->log('integration_test_failure', 'integrations', "{$provider} integration test failed.");

            return ['success' => false, 'message' => "{$provider} connection failed.", 'data' => ['status' => $status]];
        }

        $integration->forceFill(['last_used_at' => now()])->save();
        $this->activityLogService->log('integration_test_success', 'integrations', "{$provider} integration test passed.");

        return ['success' => true, 'message' => "{$provider} connection successful.", 'data' => ['status' => $status]];
    }

    private function testCustomCrm(Integration $integration): array
    {
        $baseUrl = (string) $integration->getCredential('base_url');
        $accessToken = (string) $integration->getCredential('access_token');

        if ($baseUrl === '' || $accessToken === '') {
            return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
        }

        $healthUrl = rtrim($baseUrl, '/').'/health';
        $response = Http::withToken($accessToken)
            ->timeout(8)
            ->connectTimeout(5)
            ->get($healthUrl);

        return $this->buildCrmResult($integration, $response->successful(), 'Custom CRM', $response->status());
    }
}
