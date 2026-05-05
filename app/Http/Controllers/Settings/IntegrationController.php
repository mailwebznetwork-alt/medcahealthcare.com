<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\ActivityLogService;
use App\Services\Integrations\BingWebmasterService;
use App\Services\Integrations\ClarityService;
use App\Services\Integrations\CrmService;
use App\Services\Integrations\GeminiService;
use App\Services\Integrations\GoogleBusinessProfileService;
use App\Services\Integrations\GoogleService;
use App\Services\Integrations\JustDialService;
use App\Services\Integrations\MetaService;
use App\Services\Integrations\OpenAIService;
use App\Services\Integrations\SocialService;
use App\Services\Integrations\StorageService;
use App\Services\Integrations\TwilioService;
use App\Services\Integrations\WebhookService;
use App\Services\Integrations\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IntegrationController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly GoogleService $googleService,
        private readonly GoogleBusinessProfileService $googleBusinessProfileService,
        private readonly ClarityService $clarityService,
        private readonly BingWebmasterService $bingWebmasterService,
        private readonly JustDialService $justDialService,
        private readonly GeminiService $geminiService,
        private readonly MetaService $metaService,
        private readonly CrmService $crmService,
        private readonly WhatsAppService $whatsAppService,
        private readonly TwilioService $twilioService,
        private readonly OpenAIService $openAIService,
        private readonly SocialService $socialService,
        private readonly WebhookService $webhookService,
        private readonly StorageService $storageService
    ) {}

    public function index(): JsonResponse
    {
        $this->syncDefaults();

        $data = Integration::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Integration $integration): array => $this->toResponse($integration))
            ->values()
            ->all();

        return $this->ok('Integrations fetched successfully.', $data);
    }

    public function syncGoogleReviews(Request $request)
    {
        $result = $this->googleBusinessProfileService->syncReviews();

        if (! $request->expectsJson()) {
            if ($result['success']) {
                return redirect()
                    ->route('settings.index')
                    ->with('status', __('Google reviews synced (:count).', ['count' => $result['count']]));
            }

            return redirect()
                ->route('settings.index')
                ->withErrors(['integration' => $result['message']]);
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => ['count' => $result['count']],
        ], $result['success'] ? 200 : 422);
    }

    public function show(string $name): JsonResponse
    {
        $integration = $this->findByName($name);
        if (! $integration instanceof Integration) {
            return $this->error('Integration not found.', 404);
        }

        return $this->ok('Integration fetched successfully.', $this->toResponse($integration));
    }

    public function update(Request $request, string $name)
    {
        $integration = $this->findByName($name);
        if (! $integration instanceof Integration) {
            return $this->error('Integration not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'is_enabled' => ['sometimes', 'boolean'],
            'credentials' => ['required', 'array'],
            ...$this->rulesFor($name),
        ]);

        if ($validator->fails()) {
            if (! $request->expectsJson()) {
                return redirect()
                    ->route('settings.index')
                    ->withErrors($validator)
                    ->withInput();
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'data' => $validator->errors(),
            ], 422);
        }

        try {
            $validated = $validator->validated();
            $existingCredentials = $integration->credentials;
            $incomingCredentials = $validated['credentials'];
            $resolvedCredentials = $this->resolveCredentialsForUpdate($integration->name, $existingCredentials, $incomingCredentials);

            $integration->forceFill([
                'credentials' => $resolvedCredentials,
                'is_enabled' => (bool) ($validated['is_enabled'] ?? $integration->is_enabled),
            ])->save();

            $this->activityLogService->log(
                'integration_updated',
                'integrations',
                sprintf('Integration "%s" updated by user %d.', $integration->name, (int) auth()->id())
            );

            if (! $request->expectsJson()) {
                return redirect()
                    ->route('settings.index')
                    ->with('status', __('Integration ":name" updated successfully.', ['name' => $integration->name]));
            }

            return $this->ok('Integration updated successfully.', $this->toResponse($integration->fresh()));
        } catch (\Throwable $exception) {
            Log::error('Integration update failed.', ['name' => $name, 'error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_update_failed', 'integrations', sprintf('Integration "%s" update failed.', $name));

            if (! $request->expectsJson()) {
                return redirect()
                    ->route('settings.index')
                    ->withErrors(['integration' => __('Integration update failed.')]);
            }

            return $this->error('Integration update failed.', 500);
        }
    }

    public function toggle(Request $request, string $name)
    {
        $integration = $this->findByName($name);
        if (! $integration instanceof Integration) {
            return $this->error('Integration not found.', 404);
        }

        try {
            $integration->forceFill(['is_enabled' => ! $integration->is_enabled])->save();

            $this->activityLogService->log(
                'integration_toggled',
                'integrations',
                sprintf('Integration "%s" set to %s by user %d.', $integration->name, $integration->is_enabled ? 'enabled' : 'disabled', (int) auth()->id())
            );

            if (! $request->expectsJson()) {
                return redirect()
                    ->route('settings.index')
                    ->with('status', __('Integration ":name" has been :state.', [
                        'name' => $integration->name,
                        'state' => $integration->is_enabled ? __('enabled') : __('disabled'),
                    ]));
            }

            return $this->ok('Integration toggled successfully.', [
                'name' => $integration->name,
                'is_enabled' => $integration->is_enabled,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Integration toggle failed.', ['name' => $name, 'error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_toggle_failed', 'integrations', sprintf('Integration "%s" toggle failed.', $name));

            if (! $request->expectsJson()) {
                return redirect()
                    ->route('settings.index')
                    ->withErrors(['integration' => __('Integration toggle failed.')]);
            }

            return $this->error('Integration toggle failed.', 500);
        }
    }

    public function testConnection(Request $request, string $name)
    {
        $integration = $this->findByName($name);
        if (! $integration instanceof Integration) {
            return $this->error('Integration not found.', 404);
        }

        $result = match ($name) {
            'google_services' => $this->googleService->testConnection(),
            'google_business_profile' => $this->googleBusinessProfileService->testConnection(),
            'microsoft_clarity' => $this->clarityService->testConnection(),
            'bing_webmaster' => $this->bingWebmasterService->testConnection(),
            'just_dial' => $this->justDialService->testConnection(),
            'gemini' => $this->geminiService->testConnection(),
            'meta_ads' => $this->metaService->testConnection(),
            'meta_capi' => $this->metaService->testConversionsApi(),
            'whatsapp_business_1', 'whatsapp_business_2', 'whatsapp_business_3' => $this->whatsAppService->testConnection($name),
            'twilio' => $this->twilioService->testConnection(),
            'chatgpt' => $this->openAIService->testConnection(),
            'youtube', 'linkedin', 'facebook', 'instagram' => $this->socialService->testConnection($name),
            'crm_hubspot', 'crm_salesforce', 'crm_zoho', 'crm_custom_1', 'crm_custom_2', 'crm_custom_3' => $this->crmService->testConnection($name),
            'webhook' => $this->webhookService->testConnection(),
            'aws_s3', 'cloudflare' => $this->storageService->testConnection(),
            default => ['success' => false, 'message' => 'Unsupported integration.', 'data' => []],
        };

        $this->activityLogService->log(
            $result['success'] ? 'integration_test_success' : 'integration_test_failure',
            'integrations',
            sprintf('Integration "%s" test result: %s.', $name, $result['message'])
        );

        if (! $request->expectsJson()) {
            if ($result['success']) {
                return redirect()
                    ->route('settings.index')
                    ->with('status', __('Integration ":name" test passed.', ['name' => $name]));
            }

            return redirect()
                ->route('settings.index')
                ->withErrors(['integration' => __('Integration ":name" test failed: :message', [
                    'name' => $name,
                    'message' => $result['message'],
                ])]);
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function syncDefaults(): void
    {
        foreach ($this->definitions() as $name => $type) {
            Integration::query()->firstOrCreate(
                ['name' => $name],
                ['type' => $type, 'credentials' => [], 'is_enabled' => false]
            );
        }
    }

    private function findByName(string $name): ?Integration
    {
        if (! array_key_exists($name, $this->definitions())) {
            return null;
        }

        return Integration::query()->firstOrCreate(
            ['name' => $name],
            ['type' => $this->definitions()[$name], 'credentials' => [], 'is_enabled' => false]
        );
    }

    private function rulesFor(string $name): array
    {
        $base = [
            'credentials' => ['required', 'array'],
        ];

        $map = [
            'google_services' => [
                'credentials.measurement_id' => ['required', 'string', 'max:120'],
                'credentials.property_id' => ['nullable', 'string', 'max:120'],
                'credentials.google_ads_aw_id' => ['nullable', 'string', 'max:120'],
                'credentials.container_id' => ['required', 'string', 'max:120'],
                'credentials.verification_code' => ['required', 'string', 'max:255'],
                'credentials.location_id' => ['required', 'string', 'max:120'],
                'credentials.api_key' => ['required', 'string', 'max:255'],
            ],
            'google_business_profile' => [
                'credentials.account_id' => ['required', 'string', 'max:120'],
                'credentials.location_id' => ['required', 'string', 'max:120'],
                'credentials.oauth_refresh_token' => ['required', 'string', 'max:2048'],
            ],
            'microsoft_clarity' => [
                'credentials.project_id' => ['required', 'string', 'max:120'],
            ],
            'gemini' => [
                'credentials.api_key' => ['required', 'string', 'max:255'],
                'credentials.model' => ['required', 'string', 'max:120'],
                'credentials.temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            ],
            'meta_ads' => [
                'credentials.pixel_id' => ['required', 'string', 'max:120'],
                'credentials.access_token' => ['required', 'string', 'max:255'],
            ],
            'meta_capi' => [
                'credentials.capi_pixel_id' => ['required', 'string', 'max:120'],
                'credentials.capi_access_token' => ['nullable', 'string', 'max:255'],
                'credentials.test_event_code' => ['nullable', 'string', 'max:120'],
            ],
            'whatsapp_business_1' => [
                'credentials.phone_number_id' => ['required', 'string', 'max:120'],
                'credentials.access_token' => ['required', 'string', 'max:255'],
                'credentials.webhook_verify_token' => ['required', 'string', 'max:255'],
            ],
            'whatsapp_business_2' => [
                'credentials.phone_number_id' => ['required', 'string', 'max:120'],
                'credentials.access_token' => ['required', 'string', 'max:255'],
                'credentials.webhook_verify_token' => ['required', 'string', 'max:255'],
            ],
            'whatsapp_business_3' => [
                'credentials.phone_number_id' => ['required', 'string', 'max:120'],
                'credentials.access_token' => ['required', 'string', 'max:255'],
                'credentials.webhook_verify_token' => ['required', 'string', 'max:255'],
            ],
            'twilio' => [
                'credentials.sid' => ['required', 'string', 'max:120'],
                'credentials.auth_token' => ['required', 'string', 'max:255'],
                'credentials.from_number' => ['required', 'string', 'max:40'],
            ],
            'chatgpt' => [
                'credentials.api_key' => ['required', 'string', 'max:255'],
                'credentials.model' => ['required', 'string', 'max:120'],
                'credentials.temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            ],
            'youtube' => [
                'credentials.api_key' => ['required', 'string', 'max:255'],
                'credentials.channel_id' => ['required', 'string', 'max:120'],
            ],
            'linkedin' => [
                'credentials.client_id' => ['required', 'string', 'max:255'],
                'credentials.client_secret' => ['required', 'string', 'max:255'],
                'credentials.access_token' => ['nullable', 'string', 'max:255'],
            ],
            'facebook' => [
                'credentials.page_id' => ['required', 'string', 'max:120'],
                'credentials.access_token' => ['required', 'string', 'max:255'],
            ],
            'instagram' => [
                'credentials.instagram_account_id' => ['required', 'string', 'max:120'],
                'credentials.access_token' => ['required', 'string', 'max:255'],
            ],
            'crm_hubspot' => [
                'credentials.access_token' => ['required', 'string', 'max:255'],
                'credentials.portal_id' => ['nullable', 'string', 'max:120'],
            ],
            'crm_salesforce' => [
                'credentials.instance_url' => ['required', 'url', 'max:2048'],
                'credentials.access_token' => ['required', 'string', 'max:255'],
                'credentials.client_id' => ['nullable', 'string', 'max:255'],
                'credentials.client_secret' => ['nullable', 'string', 'max:255'],
            ],
            'crm_zoho' => [
                'credentials.access_token' => ['required', 'string', 'max:255'],
                'credentials.org_id' => ['nullable', 'string', 'max:120'],
            ],
            'crm_custom_1' => [
                'credentials.crm_name' => ['required', 'string', 'max:120'],
                'credentials.base_url' => ['required', 'url', 'max:2048'],
                'credentials.access_token' => ['required', 'string', 'max:255'],
            ],
            'crm_custom_2' => [
                'credentials.crm_name' => ['required', 'string', 'max:120'],
                'credentials.base_url' => ['required', 'url', 'max:2048'],
                'credentials.access_token' => ['required', 'string', 'max:255'],
            ],
            'crm_custom_3' => [
                'credentials.crm_name' => ['required', 'string', 'max:120'],
                'credentials.base_url' => ['required', 'url', 'max:2048'],
                'credentials.access_token' => ['required', 'string', 'max:255'],
            ],
            'bing_webmaster' => [
                'credentials.site_url' => ['required', 'url', 'max:2048'],
                'credentials.api_key' => ['required', 'string', 'max:255'],
            ],
            'just_dial' => [
                'credentials.api_key' => ['required', 'string', 'max:255'],
                'credentials.profile_id' => ['required', 'string', 'max:120'],
                'credentials.endpoint_url' => ['nullable', 'url', 'max:2048'],
            ],
            'webhook' => [
                'credentials.endpoint_url' => ['required', 'url', 'max:2048'],
                'credentials.secret' => ['required', 'string', 'max:255'],
            ],
            'aws_s3' => [
                'credentials.key' => ['required', 'string', 'max:255'],
                'credentials.secret' => ['required', 'string', 'max:255'],
                'credentials.region' => ['required', 'string', 'max:120'],
                'credentials.bucket' => ['required', 'string', 'max:255'],
            ],
            'cloudflare' => [
                'credentials.api_token' => ['required', 'string', 'max:255'],
                'credentials.zone_id' => ['required', 'string', 'max:255'],
            ],
        ];

        return array_merge($base, $map[$name] ?? []);
    }

    private function toResponse(Integration $integration): array
    {
        return [
            'id' => $integration->id,
            'name' => $integration->name,
            'type' => $integration->type,
            'is_enabled' => $integration->is_enabled,
            'last_used_at' => $integration->last_used_at?->toIso8601String(),
            'credentials' => $this->maskCredentials($integration->credentials),
            'updated_at' => $integration->updated_at?->toIso8601String(),
        ];
    }

    private function maskCredentials(array $credentials): array
    {
        $masked = [];
        $sensitiveKeys = [
            'api_key',
            'access_token',
            'auth_token',
            'secret',
            'webhook_verify_token',
            'sid',
            'key',
            'verification_code',
            'client_secret',
        ];

        foreach ($credentials as $key => $value) {
            if (is_array($value)) {
                $masked[$key] = $this->maskCredentials($value);

                continue;
            }

            if ($value === null || $value === '') {
                $masked[$key] = null;

                continue;
            }

            $stringValue = (string) $value;
            if (in_array((string) $key, $sensitiveKeys, true)) {
                $masked[$key] = str_repeat('*', max(0, mb_strlen($stringValue) - 4)).mb_substr($stringValue, -4);
            } else {
                $masked[$key] = $stringValue;
            }
        }

        return $masked;
    }

    private function definitions(): array
    {
        return [
            'google_services' => 'google',
            'google_business_profile' => 'google',
            'microsoft_clarity' => 'analytics',
            'gemini' => 'ai',
            'meta_ads' => 'meta',
            'meta_capi' => 'meta',
            'whatsapp_business_1' => 'whatsapp',
            'whatsapp_business_2' => 'whatsapp',
            'whatsapp_business_3' => 'whatsapp',
            'twilio' => 'communication',
            'chatgpt' => 'ai',
            'youtube' => 'social',
            'linkedin' => 'social',
            'facebook' => 'social',
            'instagram' => 'social',
            'crm_hubspot' => 'crm',
            'crm_salesforce' => 'crm',
            'crm_zoho' => 'crm',
            'crm_custom_1' => 'crm',
            'crm_custom_2' => 'crm',
            'crm_custom_3' => 'crm',
            'bing_webmaster' => 'seo',
            'just_dial' => 'listing',
            'webhook' => 'automation',
            'aws_s3' => 'storage',
            'cloudflare' => 'storage',
        ];
    }

    private function resolveCredentialsForUpdate(string $integrationName, array $existing, array $incoming): array
    {
        $retainableKeys = [
            'access_token',
            'api_key',
            'auth_token',
            'secret',
            'webhook_verify_token',
            'oauth_refresh_token',
            'capi_access_token',
            'client_secret',
        ];

        if (! in_array($integrationName, ['meta_capi', 'google_business_profile', 'meta_ads', 'whatsapp_business_1', 'whatsapp_business_2', 'whatsapp_business_3', 'crm_hubspot', 'crm_salesforce', 'crm_zoho', 'crm_custom_1', 'crm_custom_2', 'crm_custom_3', 'bing_webmaster', 'just_dial', 'youtube', 'linkedin', 'facebook', 'instagram', 'google_services', 'gemini', 'chatgpt', 'webhook', 'aws_s3', 'cloudflare', 'twilio'], true)) {
            return $incoming;
        }

        foreach ($retainableKeys as $key) {
            $hasIncoming = array_key_exists($key, $incoming);
            $incomingValue = $incoming[$key] ?? null;
            $shouldRetain = ! $hasIncoming || $incomingValue === null || (is_string($incomingValue) && trim($incomingValue) === '');

            if ($shouldRetain && isset($existing[$key]) && is_string($existing[$key]) && $existing[$key] !== '') {
                $incoming[$key] = $existing[$key];
            }
        }

        return $incoming;
    }

    private function ok(string $message, array $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => [],
        ], $status);
    }
}
