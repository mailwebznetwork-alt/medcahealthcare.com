@php
    $jobActive = request()->routeIs('operations.job-portal.*');
    $pinActive = request()->routeIs('operations.pin-codes.*');
@endphp

<nav class="flex flex-wrap gap-0" aria-label="{{ __('Operations workspaces') }}">
    <a
        href="{{ route('operations.job-portal.overview') }}"
        @class([
            'inline-flex items-center border-b-2 px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $jobActive,
            'border-transparent text-[var(--text-secondary)] hover:border-[rgba(255,255,255,0.08)] hover:text-[var(--text-primary)]' => ! $jobActive,
        ])
    >{{ __('Job Portal') }}</a>
    <a
        href="{{ route('operations.pin-codes.overview') }}"
        @class([
            'inline-flex items-center border-b-2 px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $pinActive,
            'border-transparent text-[var(--text-secondary)] hover:border-[rgba(255,255,255,0.08)] hover:text-[var(--text-primary)]' => ! $pinActive,
        ])
    >{{ __('Pin Codes') }}</a>
</nav>
