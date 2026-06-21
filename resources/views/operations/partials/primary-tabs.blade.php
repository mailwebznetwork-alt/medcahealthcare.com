@php
    $jobActive = request()->routeIs('operations.job-portal.*');
    $pinActive = request()->routeIs('operations.pin-codes.*');
    $servicesActive = request()->routeIs('operations.services.*');
    $categoriesActive = request()->routeIs('operations.service-categories.*');
    $bookingsActive = request()->routeIs('operations.bookings.*');
@endphp

<nav class="flex flex-wrap gap-0" aria-label="{{ __('Operations workspaces') }}">
    <a
        href="{{ route('operations.job-portal.overview') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $jobActive,
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => ! $jobActive,
        ])
    >{{ __('Job Portal') }}</a>
    <a
        href="{{ route('operations.pin-codes.overview') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $pinActive,
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => ! $pinActive,
        ])
    >{{ __('Countries') }}</a>
    <a
        href="{{ route('operations.services.index') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $servicesActive,
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => ! $servicesActive,
        ])
    >{{ __('Services') }}</a>
    <a
        href="{{ route('operations.service-categories.index') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $categoriesActive,
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => ! $categoriesActive,
        ])
    >{{ __('Categories') }}</a>
    <a
        href="{{ route('operations.bookings.index') }}"
        @class([
            'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
            'border-mom-gold text-mom-gold' => $bookingsActive,
            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => ! $bookingsActive,
        ])
    >{{ __('Bookings') }}</a>
</nav>
