<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SettingsController;
use App\Models\Integration;
use App\Models\IntegrationAccount;
use App\Services\ActivityLogService;
use App\Services\Integrations\CredentialVault;
use App\Services\Integrations\GoogleBusinessProfileService;
use App\Services\Integrations\IntegrationRegistry;
use App\Services\Integrations\WhatsAppClickToChatService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use ReflectionNamedType;
use ReflectionUnionType;
use Throwable;

class IntegrationController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly GoogleBusinessProfileService $googleBusinessProfileService,
        private readonly IntegrationRegistry $registry,
        private readonly CredentialVault $credentialVault
    ) {}

    public function index(Request $request): JsonResponse|View
    {
        if (! $request->expectsJson()) {
            return app(SettingsController::class)->integrations();
        }

        $existing = Integration::query()
            ->orderBy('name')
            ->when(
                $this->hasAccountsTable(),
                fn ($query) => $query->with('accounts')
            )
            ->get()
            ->filter(fn (Integration $integration): bool => is_array($this->registry->get($integration->name)))
            ->map(fn (Integration $integration): array => $this->toResponse($integration))
            ->values()
            ->all();

        $available = collect($this->registry->all())
            ->map(fn (array $definition, string $name): array => [
                'name' => $name,
                'label' => (string) ($definition['label'] ?? $name),
                'type' => (string) ($definition['type'] ?? 'misc'),
                'is_added' => collect($existing)->pluck('name')->contains($name),
            ])
            ->values()
            ->all();

        return $this->ok('Integrations fetched successfully.', [
            'existing' => $existing,
            'available' => $available,
        ]);
    }

    public function syncGoogleReviews(Request $request)
    {
        $result = $this->googleBusinessProfileService->syncReviews();

        if (! $request->expectsJson()) {
            if ($result['success']) {
                return redirect()
                    ->route('settings.integrations')
                    ->with('status', __('Google reviews synced (:count).', ['count' => $result['count']]));
            }

            return redirect()
                ->route('settings.integrations')
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
        $integration = $this->findByName($name, withAccounts: true);
        if (! $integration instanceof Integration) {
            return $this->error('Integration not found.', 404);
        }

        return $this->ok('Integration fetched successfully.', $this->toResponse($integration));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('settings.integrations')->withErrors($validator);
        }

        $name = (string) $validator->validated()['name'];
        $definition = $this->registry->get($name);
        if (! is_array($definition)) {
            return redirect()->route('settings.integrations')->withErrors(['integration' => __('Selected integration is invalid.')]);
        }

        $integration = Integration::query()->firstOrCreate(
            ['name' => $name],
            [
                'type' => (string) $definition['type'],
                'credentials' => [],
                'is_enabled' => false,
            ]
        );

        $status = $integration->wasRecentlyCreated
            ? __('Integration ":name" added.', ['name' => $name])
            : __('Integration ":name" already exists.', ['name' => $name]);

        return redirect()->route('settings.integrations')->with('status', $status);
    }

    public function update(Request $request, string $name)
    {
        $integration = $this->findByName($name);
        if (! $integration instanceof Integration) {
            return $this->error('Integration not found.', 404);
        }

        $definition = $this->registry->get($name);
        if (! is_array($definition)) {
            return $this->error('Unsupported integration.', 422);
        }

        $validator = Validator::make($request->all(), [
            'is_enabled' => ['sometimes', 'boolean'],
            'credentials' => ['required', 'array'],
            ...$this->rulesFor($definition),
        ]);

        if ($validator->fails()) {
            if (! $request->expectsJson()) {
                return redirect()
                    ->route('settings.integrations')
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
            $existingCredentials = $this->credentialVault->decrypt($integration->credentials);
            $incomingCredentials = $validated['credentials'];
            $resolvedCredentials = $this->resolveCredentialsForUpdate($integration->name, $existingCredentials, $incomingCredentials);

            $integration->forceFill([
                'credentials' => $this->credentialVault->encrypt($resolvedCredentials),
                'is_enabled' => (bool) ($validated['is_enabled'] ?? $integration->is_enabled),
            ])->save();

            if ((bool) ($validated['is_enabled'] ?? false) === true) {
                Integration::query()
                    ->where('name', $integration->name)
                    ->whereKeyNot($integration->id)
                    ->update(['is_enabled' => false]);
            }

            $this->activityLogService->log(
                'integration_updated',
                'integrations',
                sprintf('Integration "%s" updated by user %d.', $integration->name, (int) auth()->id())
            );

            if (! $request->expectsJson()) {
                return redirect()
                    ->route('settings.integrations')
                    ->with('status', __('Integration ":name" updated successfully.', ['name' => $integration->name]));
            }

            return $this->ok('Integration updated successfully.', $this->toResponse($integration->fresh()));
        } catch (Throwable $exception) {
            Log::error('Integration update failed.', ['name' => $name, 'error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_update_failed', 'integrations', sprintf('Integration "%s" update failed.', $name));

            if (! $request->expectsJson()) {
                return redirect()
                    ->route('settings.integrations')
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
            $nextEnabledState = ! $integration->is_enabled;

            if ($nextEnabledState && ! $this->integrationHasRequiredCredentials($integration)) {
                if (! $request->expectsJson()) {
                    return redirect()
                        ->route('settings.integrations')
                        ->withErrors(['integration' => __('Configure and save required credentials before enabling ":name".', [
                            'name' => $integration->name,
                        ])]);
                }

                return $this->error('Configure required credentials before enabling this integration.', 422);
            }

            DB::transaction(function () use ($integration, $nextEnabledState): void {
                $integration->forceFill(['is_enabled' => $nextEnabledState])->save();

                if ($nextEnabledState) {
                    Integration::query()
                        ->where('name', $integration->name)
                        ->whereKeyNot($integration->id)
                        ->update(['is_enabled' => false]);
                }
            });

            $this->activityLogService->log(
                'integration_toggled',
                'integrations',
                sprintf('Integration "%s" set to %s by user %d.', $integration->name, $integration->is_enabled ? 'enabled' : 'disabled', (int) auth()->id())
            );

            if (! $request->expectsJson()) {
                return redirect()
                    ->route('settings.integrations')
                    ->with('status', __('Integration ":name" has been :state.', [
                        'name' => $integration->name,
                        'state' => $integration->is_enabled ? __('enabled') : __('disabled'),
                    ]));
            }

            return $this->ok('Integration toggled successfully.', [
                'name' => $integration->name,
                'is_enabled' => $integration->is_enabled,
            ]);
        } catch (Throwable $exception) {
            Log::error('Integration toggle failed.', ['name' => $name, 'error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_toggle_failed', 'integrations', sprintf('Integration "%s" toggle failed.', $name));

            if (! $request->expectsJson()) {
                return redirect()
                    ->route('settings.integrations')
                    ->withErrors(['integration' => __('Integration toggle failed.')]);
            }

            return $this->error('Integration toggle failed.', 500);
        }
    }

    public function testConnection(Request $request, string $name)
    {
        $integration = $this->findByName($name, withAccounts: true);
        if (! $integration instanceof Integration) {
            return $this->error('Integration not found.', 404);
        }

        $result = $this->runTest($integration);

        $this->activityLogService->log(
            $result['success'] ? 'integration_test_success' : 'integration_test_failure',
            'integrations',
            sprintf('Integration "%s" test result: %s.', $name, $result['message'])
        );

        if (! $request->expectsJson()) {
            if ($result['success']) {
                return redirect()
                    ->route('settings.integrations')
                    ->with('status', __('Integration ":name" test passed.', ['name' => $name]));
            }

            return redirect()
                ->route('settings.integrations')
                ->withErrors(['integration' => __('Integration ":name" test failed: :message', [
                    'name' => $name,
                    'message' => $result['message'],
                ])]);
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function destroy(string $name)
    {
        $integration = $this->findByName($name);
        if (! $integration instanceof Integration) {
            return redirect()->route('settings.integrations')->withErrors(['integration' => __('Integration not found.')]);
        }

        $integration->delete();

        return redirect()->route('settings.integrations')->with('status', __('Integration ":name" deleted.', ['name' => $name]));
    }

    public function storeAccount(Request $request, string $name)
    {
        if (! $this->hasAccountsTable()) {
            return redirect()->route('settings.integrations')->withErrors(['integration' => __('Integration accounts table is not migrated yet.')]);
        }

        $integration = $this->findByName($name, withAccounts: true);
        $definition = $this->registry->get($name);

        if (! $integration instanceof Integration || ! is_array($definition) || empty($definition['multi_account'])) {
            return redirect()->route('settings.integrations')->withErrors(['integration' => __('Unsupported account integration.')]);
        }

        if ($integration->accounts->count() >= 5) {
            return redirect()->route('settings.integrations')->withErrors(['integration' => __('Maximum 5 WhatsApp numbers are allowed.')]);
        }

        $accountFields = (array) ($definition['account_fields'] ?? []);
        $validator = Validator::make($request->all(), [
            'label' => ['required', 'string', 'max:120'],
            'credentials' => ['required', 'array'],
            ...$this->normalizeRules('credentials', $accountFields),
        ]);

        if ($validator->fails()) {
            return redirect()->route('settings.integrations')->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $credentials = (array) $validated['credentials'];
        $integration->accounts()->create([
            'label' => (string) $validated['label'],
            'account_identifier' => Arr::get($credentials, 'phone_number_id'),
            'credentials' => $this->credentialVault->encrypt($credentials),
            'is_enabled' => true,
        ]);

        return redirect()->route('settings.integrations')->with('status', __('WhatsApp account added.'));
    }

    public function updateClickToChat(Request $request, WhatsAppClickToChatService $whatsAppClickToChat)
    {
        $integration = $this->findByName(WhatsAppClickToChatService::INTEGRATION_NAME);
        if (! $integration instanceof Integration) {
            return redirect()->route('settings.integrations')->withErrors(['integration' => __('Add the WhatsApp integration first.')]);
        }

        $validator = Validator::make($request->all(), [
            'is_enabled' => ['sometimes', 'boolean'],
            'floating_button_enabled' => ['sometimes', 'boolean'],
            'click_numbers' => ['nullable', 'array', 'max:'.WhatsAppClickToChatService::MAX_NUMBERS],
            'click_numbers.*.display_name' => ['nullable', 'string', 'max:120'],
            'click_numbers.*.phone' => ['nullable', 'string', 'max:32'],
            'click_numbers.*.default_message' => ['nullable', 'string', 'max:1000'],
            'click_numbers.*.enabled' => ['sometimes', 'boolean'],
            'click_numbers.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('settings.integrations')->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $existing = $this->credentialVault->decrypt($integration->credentials);
        $existing['click_numbers'] = $whatsAppClickToChat->sanitizeClickNumbers((array) ($validated['click_numbers'] ?? []));
        $existing['floating_button_enabled'] = $request->boolean('floating_button_enabled');

        $integration->forceFill([
            'credentials' => $this->credentialVault->encrypt($existing),
            'is_enabled' => $request->has('is_enabled') ? $request->boolean('is_enabled') : $integration->is_enabled,
        ])->save();

        $this->activityLogService->log(
            'integration_updated',
            'integrations',
            sprintf('WhatsApp click-to-chat numbers updated by user %d.', (int) auth()->id())
        );

        return redirect()->route('settings.integrations')->with('status', __('WhatsApp click-to-chat settings saved.'));
    }

    private function findByName(string $name, bool $withAccounts = false): ?Integration
    {
        if (! is_array($this->registry->get($name))) {
            return null;
        }

        $query = Integration::query()->where('name', $name);
        if ($withAccounts && $this->hasAccountsTable()) {
            $query->with('accounts');
        }

        return $query->first();
    }

    private function toResponse(Integration $integration): array
    {
        return [
            'id' => $integration->id,
            'name' => $integration->name,
            'type' => $integration->type,
            'is_enabled' => $integration->is_enabled,
            'last_used_at' => $integration->last_used_at?->toIso8601String(),
            'credentials' => $this->credentialVault->mask(
                $this->credentialVault->decrypt($integration->credentials)
            ),
            'accounts' => $this->mapAccounts($integration),
            'updated_at' => $integration->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapAccounts(Integration $integration): array
    {
        if (! $this->hasAccountsTable()) {
            return [];
        }

        $accounts = $integration->relationLoaded('accounts')
            ? $integration->accounts
            : $integration->accounts()->get();

        return $accounts->map(function (IntegrationAccount $account): array {
            return [
                'id' => $account->id,
                'label' => $account->label,
                'account_identifier' => $account->account_identifier,
                'is_enabled' => $account->is_enabled,
                'last_used_at' => $account->last_used_at?->toIso8601String(),
                'credentials' => $this->credentialVault->mask(
                    $this->credentialVault->decrypt($account->credentials)
                ),
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, array<int, mixed>>
     */
    private function rulesFor(array $definition): array
    {
        $fields = (array) ($definition['fields'] ?? []);

        return $this->normalizeRules('credentials', $fields);
    }

    /**
     * @param  array<string, array<int, mixed>>  $fields
     * @return array<string, array<int, mixed>>
     */
    private function normalizeRules(string $prefix, array $fields): array
    {
        $rules = [];
        foreach ($fields as $field => $ruleSet) {
            $rules["{$prefix}.{$field}"] = $ruleSet;
        }

        return $rules;
    }

    private function integrationHasRequiredCredentials(Integration $integration): bool
    {
        $definition = $this->registry->get($integration->name);
        if (! is_array($definition)) {
            return true;
        }

        $decrypted = $this->credentialVault->decrypt($integration->credentials);
        foreach ((array) ($definition['fields'] ?? []) as $field => $rules) {
            if (! in_array('required', (array) $rules, true)) {
                continue;
            }

            $value = $decrypted[$field] ?? null;
            if (! is_string($value) || trim($value) === '') {
                return false;
            }
        }

        return true;
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

        if (! is_array($this->registry->get($integrationName))) {
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

    private function runTest(Integration $integration): array
    {
        $definition = $this->registry->get($integration->name);
        if (! is_array($definition)) {
            return ['success' => false, 'message' => 'Unsupported integration.', 'data' => []];
        }

        $serviceClass = (string) ($definition['service'] ?? '');
        if ($serviceClass === '' || ! class_exists($serviceClass)) {
            return ['success' => false, 'message' => 'Integration service is not available.', 'data' => []];
        }

        try {
            $service = app($serviceClass);
            $decryptedIntegration = clone $integration;
            $decryptedIntegration->credentials = $this->credentialVault->decrypt($integration->credentials);

            $account = $this->hasAccountsTable()
                ? ($integration->relationLoaded('accounts') ? $integration->accounts->first() : $integration->accounts()->first())
                : null;
            if ($account instanceof IntegrationAccount) {
                $decryptedAccount = clone $account;
                $decryptedAccount->credentials = $this->credentialVault->decrypt($account->credentials);
                if (method_exists($service, 'testConnectionWithAccount')) {
                    return $service->testConnectionWithAccount($decryptedIntegration, $decryptedAccount);
                }
            }

            if (method_exists($service, 'testConnection')) {
                $method = new \ReflectionMethod($service, 'testConnection');
                $params = $method->getParameters();
                if (count($params) === 0) {
                    return $service->testConnection();
                }

                if (count($params) >= 1) {
                    $firstType = $params[0]->getType();
                    if ($firstType instanceof ReflectionNamedType && $firstType->getName() === Integration::class) {
                        return $service->testConnection($decryptedIntegration);
                    }

                    if ($firstType instanceof ReflectionNamedType && $firstType->getName() === 'string') {
                        return $service->testConnection($decryptedIntegration->name);
                    }

                    if ($firstType instanceof ReflectionUnionType) {
                        foreach ($firstType->getTypes() as $type) {
                            if ($type->getName() === Integration::class) {
                                return $service->testConnection($decryptedIntegration);
                            }
                        }
                    }
                }

                return $service->testConnection($decryptedIntegration);
            }

            return ['success' => false, 'message' => 'Integration test method is missing.', 'data' => []];
        } catch (Throwable $exception) {
            Log::error('Integration test failed.', ['name' => $integration->name, 'error' => $exception->getMessage()]);

            return ['success' => false, 'message' => 'Integration test failed.', 'data' => []];
        }
    }

    private function hasAccountsTable(): bool
    {
        return Schema::hasTable('integration_accounts');
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
