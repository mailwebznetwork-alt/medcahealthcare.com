@php
    $active = $activeSection ?? 'appearance';
    $isSuperAdmin = auth()->check() && strtolower((string) auth()->user()?->role) === 'super_admin';
    $isBackupOperator = auth()->check() && \App\Support\BackupOperator::allows(auth()->user());
@endphp

<nav class="flex flex-wrap gap-0" aria-label="{{ __('Settings sections') }}">
    <a
        href="{{ route('settings.appearance') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $active === 'appearance',
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => $active !== 'appearance',
        ])
    >{{ __('Appearance') }}</a>
    <a
        href="{{ route('settings.global-content') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $active === 'global-content',
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => $active !== 'global-content',
        ])
    >{{ __('Global Content') }}</a>
    @if ($isBackupOperator)
        <a
            href="{{ route('settings.backup') }}"
            @class([
                'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
                'border-mom-gold text-mom-gold' => $active === 'backup',
                'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => $active !== 'backup',
            ])
        >{{ __('Backup') }}</a>
    @endif
    @if ($isSuperAdmin)
        <a
            href="{{ route('settings.maintenance') }}"
            @class([
                'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
                'border-mom-gold text-mom-gold' => $active === 'maintenance',
                'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => $active !== 'maintenance',
            ])
        >{{ __('Maintenance') }}</a>
    @endif
    <a
        href="{{ route('system.integrations') }}"
        class="inline-flex items-center border-b border-transparent px-5 py-3.5 text-sm font-semibold tracking-wide text-[var(--text-muted)] transition-colors duration-320 ease-premium hover:text-[var(--text-secondary)]"
    >{{ __('Integrations → System') }}</a>
</nav>
