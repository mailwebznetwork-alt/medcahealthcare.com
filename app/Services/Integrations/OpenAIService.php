<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
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
            $temperature = (float) ($integration->getCredential('temperature') ?? 0.3);

            if ($apiKey === '' || $model === '') {
                return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
            }

            $response = Http::withToken($apiKey)
                ->timeout(12)
                ->connectTimeout(5)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => 'ping'],
                    ],
                    'max_tokens' => 1,
                    'temperature' => $temperature,
                ]);

            if (! $response->successful()) {
                $this->activityLogService->log('integration_test_failure', 'integrations', 'OpenAI integration test failed.');

                return ['success' => false, 'message' => 'OpenAI connection failed.', 'data' => ['status' => $response->status()]];
            }

            $integration->forceFill(['last_used_at' => now()])->save();
            $this->activityLogService->log('integration_test_success', 'integrations', 'OpenAI integration test passed.');

            return ['success' => true, 'message' => 'OpenAI connection successful.', 'data' => ['status' => $response->status()]];
        } catch (\Throwable $exception) {
            Log::error('OpenAI integration test failed.', ['error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_test_failure', 'integrations', 'OpenAI integration test failed.');

            return ['success' => false, 'message' => 'OpenAI integration test failed.', 'data' => []];
        }
    }
}
