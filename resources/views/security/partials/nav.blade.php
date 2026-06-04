<nav class="flex flex-wrap gap-0" aria-label="{{ __('Security sections') }}">
    @foreach ([
        'overview' => __('Overview'),
        'audit' => __('Audit'),
        'activity' => __('Activity'),
        'failed-logins' => __('Failed logins'),
        'access-events' => __('Access events'),
        'firewall' => __('Firewall'),
    ] as $anchor => $label)
        <a
            href="{{ route('modules.security') }}#security-{{ $anchor }}"
            @class([
                'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
                'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]',
            ])
        >{{ $label }}</a>
    @endforeach
</nav>
