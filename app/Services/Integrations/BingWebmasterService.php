<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BingWebmasterService
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function testConnection(): array
    {
        try {
            $integration = Integration::query()->where('name', 'bing_webmaster')->first();

            if (! $integration instanceof Integration || ! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Integration disabled.', 'data' => []];
            }

            $apiKey = (string) $integration->getCredential('api_key');
            $siteUrl = (string) $integration->getCredential('site_url');
            if ($apiKey === '' || $siteUrl === '') {
                return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
            }

            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->post('https://ssl.bing.com/webmaster/api.svc/json/GetUserSites', [
                    'apikey' => $apiKey,
                ]);

            if (! $response->successful()) {
                $this->activityLogService->log('integration_test_failure', 'integrations', 'Bing Webmaster test failed.');

                return ['success' => false, 'message' => 'Bing Webmaster connection failed.', 'data' => ['status' => $response->status()]];
            }

            $integration->forceFill(['last_used_at' => now()])->save();
            $this->activityLogService->log('integration_test_success', 'integrations', 'Bing Webmaster test passed.');

            return ['success' => true, 'message' => 'Bing Webmaster connection successful.', 'data' => ['status' => $response->status()]];
        } catch (\Throwable $exception) {
            Log::error('Bing Webmaster test failed.', ['error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_test_failure', 'integrations', 'Bing Webmaster test failed.');

            return ['success' => false, 'message' => 'Bing Webmaster test failed.', 'data' => []];
        }
    }
}
