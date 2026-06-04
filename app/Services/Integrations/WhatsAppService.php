<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly WhatsAppClickToChatService $clickToChatService,
    ) {}

    public function testConnection(string $integrationName = 'whatsapp_business_1'): array
    {
        if ($integrationName === WhatsAppClickToChatService::INTEGRATION_NAME) {
            $active = $this->clickToChatService->activeNumbers();
            if ($active === []) {
                return [
                    'success' => false,
                    'message' => 'No enabled WhatsApp numbers. Add at least one number in Configure.',
                    'data' => [],
                ];
            }

            return [
                'success' => true,
                'message' => sprintf('Click-to-WhatsApp ready (%d active number(s)).', count($active)),
                'data' => ['numbers' => array_map(fn ($n) => $n->displayName, $active)],
            ];
        }

        try {
            $integration = Integration::query()->where('name', $integrationName)->first();

            if (! $integration instanceof Integration || ! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Integration disabled.', 'data' => []];
            }

            $phoneNumberId = (string) $integration->getCredential('phone_number_id');
            $accessToken = (string) $integration->getCredential('access_token');
            $webhookToken = (string) $integration->getCredential('webhook_verify_token');

            if ($phoneNumberId === '' || $accessToken === '' || $webhookToken === '') {
                return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
            }

            $integration->forceFill(['last_used_at' => now()])->save();
            $this->activityLogService->log('integration_test_success', 'integrations', 'WhatsApp integration test passed.');

            return ['success' => true, 'message' => 'WhatsApp webhook verification simulation passed.', 'data' => ['integration' => $integrationName]];
        } catch (\Throwable $exception) {
            Log::error('WhatsApp integration test failed.', ['error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_test_failure', 'integrations', 'WhatsApp integration test failed.');

            return ['success' => false, 'message' => 'WhatsApp integration test failed.', 'data' => []];
        }
    }
}
