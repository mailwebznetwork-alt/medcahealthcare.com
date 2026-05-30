<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function testConnection(Integration $integration): array
    {
        try {
            if (! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Integration disabled.', 'data' => []];
            }

            $apiKey = (string) $integration->getCredential('api_key');
            $model = (string) $integration->getCredential('model');

            if ($apiKey === '' || $model === '') {
                return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
            }

            $response = Http::timeout(12)
                ->connectTimeout(5)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                    [
                        'contents' => [
                            ['parts' => [['text' => 'ping']]],
                        ],
                    ]
                );

            if (! $response->successful()) {
                $this->activityLogService->log('integration_test_failure', 'integrations', 'Gemini integration test failed.');

                return ['success' => false, 'message' => 'Gemini connection failed.', 'data' => ['status' => $response->status()]];
            }

            $integration->forceFill(['last_used_at' => now()])->save();
            $this->activityLogService->log('integration_test_success', 'integrations', 'Gemini integration test passed.');

            return ['success' => true, 'message' => 'Gemini connection successful.', 'data' => ['status' => $response->status()]];
        } catch (\Throwable $exception) {
            Log::error('Gemini integration test failed.', ['error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_test_failure', 'integrations', 'Gemini integration test failed.');

            return ['success' => false, 'message' => 'Gemini integration test failed.', 'data' => []];
        }
    }
}
