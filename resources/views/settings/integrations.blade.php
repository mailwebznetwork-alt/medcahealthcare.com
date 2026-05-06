<x-app-layout
    :page-title="__('Settings')"
    :welcome-line="__('Integrations workspace for platform and channel connections.')"
>
    @if (session('status'))
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ session('status') }}</p>
    @endif
    @if ($errors->has('integration'))
        <p class="mom-body-text mb-6 text-[var(--danger)]" role="alert">{{ $errors->first('integration') }}</p>
    @endif

    @php
        $isSuperAdmin = auth()->check() && strtolower((string) auth()->user()?->role) === 'super_admin';
    @endphp

    <nav class="mom-card mb-8 flex flex-wrap gap-x-6 gap-y-2 p-4 text-[13px] text-[var(--text-secondary)]" aria-label="{{ __('Settings sections') }}">
        <a href="#integrations" class="hover:text-[var(--accent-gold)]">{{ __('Integrations') }}</a>
        <a href="#webhooks" class="hover:text-[var(--accent-gold)]">{{ __('Webhooks') }}</a>
        @if ($isSuperAdmin)
            <a href="#backup" class="hover:text-[var(--accent-gold)]">{{ __('Backup') }}</a>
            <a href="#maintenance" class="hover:text-[var(--accent-gold)]">{{ __('Maintenance') }}</a>
        @endif
    </nav>

    <div id="integrations" class="space-y-8">
    <section class="mom-card p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="mom-section-title">{{ __('Add Integration') }}</h2>
                <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('Add only integrations that are not already configured.') }}</p>
            </div>
        </div>
        <form method="post" action="{{ route('admin.settings.integrations.store') }}" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf
            <label class="block min-w-[22rem]">
                <span class="mom-micro mb-1 block">{{ __('Integration') }}</span>
                <select name="name" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                    @foreach ($availableIntegrations as $option)
                        <option value="{{ $option['name'] }}">
                            {{ $option['label'] }} ({{ $option['type'] }}){{ ($option['is_added'] ?? false) ? ' - Added' : '' }}
                        </option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]" @disabled(count($availableIntegrations) === 0)>
                {{ __('Add Integration') }}
            </button>
        </form>
    </section>

    <section class="mom-card mt-8 p-6">
        <h2 class="mom-section-title">{{ __('Integrations') }}</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[70rem] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Integration Name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Last Used') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                    @forelse ($integrations as $integration)
                        @php
                            $definition = $definitions[$integration->name] ?? null;
                            $label = $definition['label'] ?? str_replace('_', ' ', $integration->name);
                            $fields = $definition['fields'] ?? [];
                            $decrypted = $credentialVault->decrypt($integration->credentials);
                        @endphp
                        <tr>
                            <td class="px-4 py-3">{{ $label }}</td>
                            <td class="px-4 py-3">{{ str_replace('_', ' ', $integration->type) }}</td>
                            <td class="px-4 py-3">
                                <span class="{{ $integration->is_enabled ? 'text-[var(--success)]' : 'text-[var(--text-muted)]' }}">
                                    {{ $integration->is_enabled ? __('Enabled') : __('Disabled') }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $integration->last_used_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? __('Never') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <details class="inline-block">
                                        <summary class="mom-cta-primary cursor-pointer !px-3 !py-2 !text-[11px]">{{ __('Configure') }}</summary>
                                        <div class="mt-3 w-[28rem] rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(10,15,28,0.92)] p-4">
                                            <form method="post" action="{{ route('admin.settings.integrations.update', $integration->name) }}" class="space-y-3">
                                                @csrf
                                                <input type="hidden" name="is_enabled" value="{{ $integration->is_enabled ? '1' : '0' }}">
                                                @foreach ($fields as $field => $rules)
                                                    <label class="block">
                                                        <span class="mom-micro mb-1 block">{{ str_replace('_', ' ', $field) }}</span>
                                                        <input
                                                            type="{{ str_contains($field, 'token') || str_contains($field, 'key') || str_contains($field, 'secret') ? 'password' : 'text' }}"
                                                            name="credentials[{{ $field }}]"
                                                            value="{{ old("credentials.$field", $decrypted[$field] ?? '') }}"
                                                            class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]"
                                                            autocomplete="off"
                                                        >
                                                    </label>
                                                @endforeach
                                                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Save') }}</button>
                                            </form>

                                            @if (!empty($definition['multi_account']) && $integration->name === 'whatsapp_business')
                                                <div class="mt-4 border-t border-[var(--border-panel-soft)] pt-4">
                                                    <h4 class="mom-micro mb-2">{{ __('Add WhatsApp Number (max 5)') }}</h4>
                                                    <form method="post" action="{{ route('admin.settings.integrations.accounts.store', $integration->name) }}" class="space-y-2">
                                                        @csrf
                                                        <input name="label" placeholder="{{ __('Account label') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                                                        <input name="credentials[phone_number_id]" placeholder="{{ __('Phone number ID') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                                                        <input name="credentials[access_token]" type="password" placeholder="{{ __('Access token') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                                                        <input name="credentials[webhook_verify_token]" type="password" placeholder="{{ __('Webhook verify token') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                                                        <button type="submit" class="mom-cta-ghost !px-3 !py-2 !text-[11px]" @disabled($hasIntegrationAccountsTable && $integration->accounts->count() >= 5)>{{ __('Add WhatsApp Number') }}</button>
                                                    </form>
                                                    <ul class="mt-3 space-y-1 text-xs">
                                                        @if (! $hasIntegrationAccountsTable)
                                                            <li>{{ __('Run migrations to enable WhatsApp multi-account.') }}</li>
                                                        @endif
                                                        @foreach ($hasIntegrationAccountsTable ? $integration->accounts : [] as $account)
                                                            <li>{{ $account->label }} ({{ $account->account_identifier }})</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                    <form method="post" action="{{ route('admin.settings.integrations.toggle', $integration->name) }}">
                                        @csrf
                                        @method('patch')
                                        <button type="submit" class="mom-cta-ghost !px-3 !py-2 !text-[11px]">{{ $integration->is_enabled ? __('Disable') : __('Enable') }}</button>
                                    </form>
                                    <form method="post" action="{{ route('admin.settings.integrations.test', $integration->name) }}">
                                        @csrf
                                        <button type="submit" class="mom-cta-ghost !px-3 !py-2 !text-[11px]">{{ __('Test') }}</button>
                                    </form>
                                    <form method="post" action="{{ route('admin.settings.integrations.destroy', $integration->name) }}" onsubmit="return confirm('{{ __('Delete this integration?') }}')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="mom-cta-ghost !px-3 !py-2 !text-[11px]">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No integrations found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mom-card mt-8 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="mom-section-title">{{ __('Google Business Review Feed') }}</h3>
                <p class="mom-subtext mt-2">{{ __('Manual sync or wait for scheduled 4-hour sync cycle.') }}</p>
            </div>
            <form method="post" action="{{ route('admin.settings.integrations.google-business-profile.sync-reviews') }}">
                @csrf
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Sync Google reviews') }}</button>
            </form>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Reviewer') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Rating') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Comment') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Reviewed at') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                    @forelse ($googleBusinessReviews as $review)
                        <tr>
                            <td class="px-4 py-3">{{ $review->reviewer_name ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $review->star_rating ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $review->comment ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $review->review_time?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No Google reviews synced yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mom-card mt-8 p-6">
        <h2 class="mom-section-title">{{ __('Integration Matrix') }}</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[36rem] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Total') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Active') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Inactive') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                    @forelse ($matrixSummary as $type => $row)
                        <tr>
                            <td class="px-4 py-3">{{ str_replace('_', ' ', $type) }}</td>
                            <td class="px-4 py-3">{{ $row['total'] }}</td>
                            <td class="px-4 py-3">{{ $row['active'] }}</td>
                            <td class="px-4 py-3">{{ $row['inactive'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No integration data found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    </div>

    <section id="webhooks" class="mom-card mt-8 p-6">
        <h2 class="mom-section-title">{{ __('Outbound webhook events') }}</h2>
        <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('Configure the Webhook integration with endpoint URL and secret; when enabled, these events POST JSON to your receiver.') }}</p>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Event key') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('When it fires') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                    @foreach ($webhookEvents as $row)
                        <tr>
                            <td class="px-4 py-3 font-mono text-[12px]">{{ $row['key'] }}</td>
                            <td class="px-4 py-3">{{ $row['description'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($isSuperAdmin)
        <section id="backup" class="mom-card mt-8 p-6">
            <h2 class="mom-section-title">{{ __('Database backup') }}</h2>
            <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('Creates a timestamped copy of the SQLite database under storage/app/backups. Other database drivers require a manual dump.') }}</p>
            <form method="post" action="{{ route('settings.system.backup') }}" class="mt-4 flex flex-wrap items-center gap-3">
                @csrf
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Run backup now') }}</button>
            </form>
            @if ($backupFiles !== [])
                <div class="mt-6">
                    <h3 class="mom-micro mb-2">{{ __('Recent backup files') }}</h3>
                    <ul class="space-y-1 text-[13px] text-[var(--text-secondary)]">
                        @foreach ($backupFiles as $path)
                            <li class="font-mono text-[12px]">{{ basename($path) }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <section id="maintenance" class="mom-card mt-8 p-6">
            <h2 class="mom-section-title">{{ __('Maintenance mode') }}</h2>
            <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
                {{ __('Uses Laravel maintenance mode. Set SETTINGS_OPERATIONS_TOKEN and SETTINGS_MAINTENANCE_BYPASS_SECRET in .env before enabling.') }}
            </p>
            @if ($maintenanceActive)
                <p class="mom-body-text mt-3 text-[var(--warning)]" role="status">{{ __('Maintenance mode is currently active.') }}</p>
            @endif
            @if (! $operationsConfigured)
                <p class="mom-body-text mt-3 text-[var(--danger)]">{{ __('SETTINGS_OPERATIONS_TOKEN is not set — maintenance forms are disabled.') }}</p>
            @endif
            @if (! $maintenanceSecretConfigured)
                <p class="mom-body-text mt-2 text-[var(--danger)]">{{ __('SETTINGS_MAINTENANCE_BYPASS_SECRET must be set before you can enable maintenance from this screen.') }}</p>
            @endif

            <form method="post" action="{{ route('settings.system.maintenance') }}" class="mt-6 space-y-3">
                @csrf
                <input type="hidden" name="maintenance_action" value="down">
                <label class="block max-w-md">
                    <span class="mom-micro mb-1 block">{{ __('Operations token') }}</span>
                    <input
                        type="password"
                        name="settings_operations_token"
                        autocomplete="off"
                        class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]"
                        @disabled(! $operationsConfigured || ! $maintenanceSecretConfigured)
                    >
                </label>
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]" @disabled(! $operationsConfigured || ! $maintenanceSecretConfigured)>{{ __('Put site in maintenance') }}</button>
            </form>

            <form method="post" action="{{ route('settings.system.maintenance') }}" class="mt-8 space-y-3 border-t border-[var(--border-panel-soft)] pt-6">
                @csrf
                <input type="hidden" name="maintenance_action" value="up">
                <label class="block max-w-md">
                    <span class="mom-micro mb-1 block">{{ __('Operations token') }}</span>
                    <input
                        type="password"
                        name="settings_operations_token"
                        autocomplete="off"
                        class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]"
                        @disabled(! $operationsConfigured)
                    >
                </label>
                <button type="submit" class="mom-cta-ghost !px-3 !py-2 !text-[11px]" @disabled(! $operationsConfigured)>{{ __('Bring site live') }}</button>
            </form>
        </section>
    @endif
</x-app-layout>
