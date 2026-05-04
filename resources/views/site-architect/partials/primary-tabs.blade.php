@php
    $pagesActive = request()->routeIs('site-architect.pages.*');
@endphp

<nav class="flex flex-wrap gap-0" aria-label="{{ __('Site Architect workspaces') }}">
    <a
        href="{{ route('site-architect.pages.index') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $pagesActive,
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => ! $pagesActive,
        ])
    >{{ __('Pages') }}</a>
</nav>
