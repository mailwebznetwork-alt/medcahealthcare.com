@php
    $active = $activeSection ?? 'integrations';
    $isSuperAdmin = auth()->check() && strtolower((string) auth()->user()?->role) === 'super_admin';
@endphp

<nav class="flex flex-wrap gap-0" aria-label="{{ __('Settings sections') }}">
    <a
        href="{{ route('settings.integrations') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $active === 'integrations',
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => $active !== 'integrations',
        ])
    >{{ __('Integrations') }}</a>
    <a
        href="{{ route('settings.webhooks') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $active === 'webhooks',
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => $active !== 'webhooks',
        ])
    >{{ __('Webhooks') }}</a>
    @if ($isSuperAdmin)
        <a
            href="{{ route('settings.backup') }}"
            @class([
                'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
                'border-mom-gold text-mom-gold' => $active === 'backup',
                'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => $active !== 'backup',
            ])
        >{{ __('Backup') }}</a>
        <a
            href="{{ route('settings.maintenance') }}"
            @class([
                'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
                'border-mom-gold text-mom-gold' => $active === 'maintenance',
                'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => $active !== 'maintenance',
            ])
        >{{ __('Maintenance') }}</a>
    @endif
</nav>
