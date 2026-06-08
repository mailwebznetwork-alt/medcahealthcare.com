@php
    use App\Services\Integrations\WhatsAppClickToChatService;

    $whatsAppSvc = app(WhatsAppClickToChatService::class);
    $businessIntegration = $whatsAppSvc->businessApiIntegration();
    $businessDefinition = $definitions[WhatsAppClickToChatService::BUSINESS_API_INTEGRATION_NAME] ?? null;
    $decrypted = $credentialVault->decrypt($integration->credentials);
    $storedNumbers = is_array($decrypted['click_numbers'] ?? null) ? $decrypted['click_numbers'] : [];
    $floatingEnabled = (bool) ($decrypted['floating_button_enabled'] ?? true);

    $slots = [];
    for ($i = 0; $i < WhatsAppClickToChatService::MAX_NUMBERS; $i++) {
        $slots[] = $storedNumbers[$i] ?? [
            'display_name' => '',
            'phone' => '',
            'default_message' => '',
            'enabled' => false,
            'sort_order' => $i + 1,
        ];
    }
@endphp

<p class="mom-subtext mb-4">{{ __('Click-to-WhatsApp: up to five department numbers. Business API is optional under Advanced Settings.') }}</p>

<form method="post" action="{{ route('admin.settings.integrations.whatsapp.click-to-chat') }}" class="space-y-4">
    @csrf
    <label class="flex items-center gap-3">
        <input type="hidden" name="is_enabled" value="0">
        <input type="checkbox" name="is_enabled" value="1" class="rounded border-[var(--border-panel-soft)]" @checked($integration->is_enabled)>
        <span class="mom-micro">{{ __('Enable on public site') }}</span>
    </label>
    <label class="flex items-center gap-3">
        <input type="hidden" name="floating_button_enabled" value="0">
        <input type="checkbox" name="floating_button_enabled" value="1" class="rounded border-[var(--border-panel-soft)]" @checked($floatingEnabled)>
        <span class="mom-micro">{{ __('Floating button') }}</span>
    </label>

    <div class="max-h-[24rem] space-y-3 overflow-y-auto custom-scrollbar pr-1">
        @foreach ($slots as $index => $slot)
            <details class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(0,0,0,0.12)]" @if(filled($slot['phone'] ?? '')) open @endif>
                <summary class="cursor-pointer px-3 py-2 text-sm font-semibold text-[var(--text-primary)]">
                    {{ __('Number :n', ['n' => $index + 1]) }}
                    @if (filled($slot['display_name'] ?? ''))
                        <span class="mom-subtext font-normal">— {{ $slot['display_name'] }}</span>
                    @endif
                </summary>
                <div class="grid gap-3 border-t border-[color:var(--border-tabstrip-divider)] p-3">
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('Display name') }}</span>
                        <input type="text" name="click_numbers[{{ $index }}][display_name]" value="{{ old("click_numbers.$index.display_name", $slot['display_name'] ?? '') }}" class="mom-input w-full" placeholder="{{ __('Customer Care') }}">
                    </label>
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('WhatsApp number') }}</span>
                        <input type="text" name="click_numbers[{{ $index }}][phone]" value="{{ old("click_numbers.$index.phone", $slot['phone'] ?? '') }}" class="mom-input w-full" placeholder="918884999002">
                    </label>
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('Default message') }}</span>
                        <textarea name="click_numbers[{{ $index }}][default_message]" rows="2" class="mom-input w-full">{{ old("click_numbers.$index.default_message", $slot['default_message'] ?? '') }}</textarea>
                    </label>
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="flex items-center gap-2">
                            <input type="hidden" name="click_numbers[{{ $index }}][enabled]" value="0">
                            <input type="checkbox" name="click_numbers[{{ $index }}][enabled]" value="1" @checked(old("click_numbers.$index.enabled", $slot['enabled'] ?? false))>
                            <span class="mom-micro">{{ __('Enabled') }}</span>
                        </label>
                        <label class="block w-24">
                            <span class="mom-micro mb-1 block">{{ __('Order') }}</span>
                            <input type="number" name="click_numbers[{{ $index }}][sort_order]" min="0" max="99" value="{{ old("click_numbers.$index.sort_order", $slot['sort_order'] ?? ($index + 1)) }}" class="mom-input w-full">
                        </label>
                    </div>
                </div>
            </details>
        @endforeach
    </div>

    <button type="submit" class="mom-cta-primary mom-cta-compact">{{ __('Save') }}</button>
</form>

<details class="mt-4 border-t border-[color:var(--border-tabstrip-divider)] pt-4">
    <summary class="mom-micro cursor-pointer font-semibold uppercase tracking-wide">{{ __('Advanced — WhatsApp Business API') }}</summary>
    <p class="mom-subtext mt-2">{{ __('Webhooks and automation use Cloud API credentials. Not required for website click-to-chat links.') }}</p>

    <div class="mt-4">
        @unless ($businessIntegration)
            <form method="post" action="{{ route('admin.settings.integrations.store') }}">
                @csrf
                <input type="hidden" name="name" value="{{ WhatsAppClickToChatService::BUSINESS_API_INTEGRATION_NAME }}">
                <button type="submit" class="mom-cta-ghost mom-cta-compact">{{ __('Enable Business API') }}</button>
            </form>
        @endunless

        @if ($businessIntegration && is_array($businessDefinition))
            <form method="post" action="{{ route('admin.settings.integrations.accounts.store', WhatsAppClickToChatService::BUSINESS_API_INTEGRATION_NAME) }}" class="mt-2 space-y-2">
                @csrf
                <input name="label" placeholder="{{ __('Account label') }}" class="mom-input w-full" required>
                <input name="credentials[phone_number_id]" placeholder="{{ __('Phone Number ID') }}" class="mom-input w-full" required>
                <input name="credentials[access_token]" type="password" placeholder="{{ __('Access Token') }}" class="mom-input w-full" required>
                <input name="credentials[webhook_verify_token]" type="password" placeholder="{{ __('Webhook Verify Token') }}" class="mom-input w-full" required>
                <button type="submit" class="mom-cta-ghost mom-cta-compact" @disabled($hasIntegrationAccountsTable && $businessIntegration->accounts->count() >= 5)>{{ __('Add API account') }}</button>
            </form>
            <ul class="mom-subtext mt-3 space-y-1">
                @foreach ($hasIntegrationAccountsTable ? $businessIntegration->accounts : [] as $account)
                    <li>{{ $account->label }} — ID {{ $account->account_identifier }}</li>
                @endforeach
            </ul>
            <div class="mt-3 flex flex-wrap gap-2">
                <form method="post" action="{{ route('admin.settings.integrations.toggle', WhatsAppClickToChatService::BUSINESS_API_INTEGRATION_NAME) }}">
                    @csrf
                    @method('patch')
                    <button type="submit" class="mom-cta-ghost mom-cta-compact">{{ $businessIntegration->is_enabled ? __('Disable API') : __('Enable API') }}</button>
                </form>
                <form method="post" action="{{ route('admin.settings.integrations.test', WhatsAppClickToChatService::BUSINESS_API_INTEGRATION_NAME) }}">
                    @csrf
                    <button type="submit" class="mom-cta-ghost mom-cta-compact">{{ __('Test API') }}</button>
                </form>
            </div>
        @endif
    </div>
</details>
