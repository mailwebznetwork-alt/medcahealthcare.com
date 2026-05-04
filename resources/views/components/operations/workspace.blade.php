@props([
    'pageTitle' => null,
    'welcomeLine' => null,
])

@php
    $resolvedPageTitle = $pageTitle;
    if ($resolvedPageTitle === null) {
        $resolvedPageTitle = match (true) {
            request()->routeIs('operations.services.*') => __('Enterprise Services'),
            request()->routeIs('operations.bookings.*') => __('Bookings'),
            default => __('Operations'),
        };
    }
    $resolvedWelcome = $welcomeLine ?? match (true) {
        request()->routeIs('operations.services.*') => __('Service operations, SEO, AEO, GEO, and reusable content by service code.'),
        request()->routeIs('operations.bookings.*') => __('Lead intake and conversion — simple queues, no CRM overhead.'),
        default => __('Run-state, hiring, coverage, and operational management workspace.'),
    };
@endphp

<x-app-layout
    :page-title="$resolvedPageTitle"
    :welcome-line="$resolvedWelcome"
>
    <div class="operations-workspace">
        <div class="mom-backend-tabstrip">
            @include('operations.partials.primary-tabs')
        </div>

        @if (request()->routeIs('operations.job-portal.*', 'operations.pin-codes.*', 'operations.services.*'))
            <div
                class="mom-backend-toolbar-row mom-sticky-toolbar sticky top-[72px] z-20 -mx-8 px-8 py-3.5"
            >
                @if (request()->routeIs('operations.job-portal.*'))
                    @include('operations.job-portal.partials.toolbar')
                @elseif (request()->routeIs('operations.pin-codes.*'))
                    @include('operations.pin-codes.partials.toolbar')
                @else
                    @include('operations.services.partials.toolbar')
                @endif
            </div>
        @endif

        {{-- Secondary tabs partial kept: operations.partials.secondary-tabs --}}
        <div @class(['mt-10' => ! request()->routeIs('operations.job-portal.*', 'operations.pin-codes.*', 'operations.services.*'), 'mt-8' => request()->routeIs('operations.job-portal.*', 'operations.pin-codes.*', 'operations.services.*')])>
            {{ $slot }}
        </div>
    </div>
</x-app-layout>
