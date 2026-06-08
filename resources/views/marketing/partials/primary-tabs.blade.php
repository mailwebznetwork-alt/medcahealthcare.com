@php
    $active = $activeSection ?? (request()->routeIs('marketing.intelligence', 'modules.marketing.intelligence') ? 'intelligence' : 'dashboard');
@endphp

<nav class="flex flex-wrap gap-0" aria-label="{{ __('Marketing workspaces') }}">
    <a
        href="{{ route('marketing.dashboard') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $active === 'dashboard',
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => $active !== 'dashboard',
        ])
    >{{ __('Dashboard') }}</a>
    <a
        href="{{ route('marketing.intelligence') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $active === 'intelligence',
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => $active !== 'intelligence',
        ])
    >{{ __('Intelligence') }}</a>
</nav>
