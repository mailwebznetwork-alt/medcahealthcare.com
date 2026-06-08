<section class="mom-card p-6">
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
        <button type="submit" class="mom-cta-primary mom-cta-compact" @disabled(! $operationsConfigured || ! $maintenanceSecretConfigured)>{{ __('Put site in maintenance') }}</button>
    </form>

    <form method="post" action="{{ route('settings.system.maintenance') }}" class="mt-8 space-y-3 border-t border-[color:var(--border-tabstrip-divider)] pt-6">
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
        <button type="submit" class="mom-cta-ghost mom-cta-compact" @disabled(! $operationsConfigured)>{{ __('Bring site live') }}</button>
    </form>
</section>
