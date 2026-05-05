<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function testConnection(Integration $integration): array
    {
        try {
            if (! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Integration disabled.', 'data' => []];
            }

            $sid = (string) $integration->getCredential('sid');
            $authToken = (string) $integration->getCredential('auth_token');
            $fromNumber = (string) $integration->getCredential('from_number');

            if ($sid === '' || $authToken === '' || $fromNumber === '') {
                return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
            }

            $response = Http::withBasicAuth($sid, $authToken)
                ->timeout(8)
                ->connectTimeout(5)
                ->asForm()
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}.json");

            if (! $response->successful()) {
                $this->activityLogService->log('integration_test_failure', 'integrations', 'Twilio integration test failed.');

                return ['success' => false, 'message' => 'Twilio connection failed.', 'data' => ['status' => $response->status()]];
            }

            $integration->forceFill(['last_used_at' => now()])->save();
            $this->activityLogService->log('integration_test_success', 'integrations', 'Twilio integration test passed.');

            return ['success' => true, 'message' => 'Twilio connection successful.', 'data' => ['status' => $response->status()]];
        } catch (\Throwable $exception) {
            Log::error('Twilio integration test failed.', ['error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_test_failure', 'integrations', 'Twilio integration test failed.');

            return ['success' => false, 'message' => 'Twilio integration test failed.', 'data' => []];
        }
    }
}
