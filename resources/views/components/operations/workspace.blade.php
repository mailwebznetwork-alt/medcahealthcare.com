@props([
    'pageTitle' => null,
    'welcomeLine' => null,
])

@php
    $resolvedPageTitle = $pageTitle;
    if ($resolvedPageTitle === null) {
        $resolvedPageTitle = match (true) {
            request()->routeIs('operations.services.*') => __('Enterprise Services'),
            request()->routeIs('operations.service-categories.*') => __('Service Categories'),
            request()->routeIs('operations.bookings.*') => __('Bookings'),
            default => __('Operations'),
        };
    }
    $resolvedWelcome = $welcomeLine ?? match (true) {
        request()->routeIs('operations.services.*') => __('Service operations, SEO, AEO, GEO, and reusable content by service code.'),
        request()->routeIs('operations.bookings.*') => __('Lead intake and conversion — simple queues, no CRM overhead.'),
        default => __('Run-state, hiring, coverage, and operational management workspace.'),
    };
    $showToolbar = request()->routeIs(
        'operations.job-portal.*',
        'operations.pin-codes.*',
        'operations.services.*',
        'operations.service-categories.*',
    );
@endphp

<x-admin.workspace
    :page-title="$resolvedPageTitle"
    :welcome-line="$resolvedWelcome"
>
    @if ($showToolbar)
        <x-slot:toolbar>
            @if (request()->routeIs('operations.job-portal.*'))
                @include('operations.job-portal.partials.toolbar')
            @elseif (request()->routeIs('operations.pin-codes.*'))
                @include('operations.pin-codes.partials.toolbar')
            @elseif (request()->routeIs('operations.service-categories.*'))
                @include('operations.service-categories.partials.toolbar')
            @else
                @include('operations.services.partials.toolbar')
            @endif
        </x-slot:toolbar>
    @endif

    {{ $slot }}
</x-admin.workspace>
