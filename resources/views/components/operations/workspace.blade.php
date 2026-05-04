<x-app-layout
    :page-title="__('Operations')"
    :welcome-line="__('Run-state, hiring, coverage, and operational management workspace.')"
>
    <div class="operations-workspace">
        <div class="border-b border-[var(--border-panel-soft)]">
            @include('operations.partials.primary-tabs')
        </div>

        @if (request()->routeIs('operations.job-portal.*', 'operations.pin-codes.*'))
            <div
                class="mom-sticky-toolbar sticky top-[72px] z-20 -mx-8 px-8 py-4"
            >
                @if (request()->routeIs('operations.job-portal.*'))
                    @include('operations.job-portal.partials.toolbar')
                @else
                    @include('operations.pin-codes.partials.toolbar')
                @endif
            </div>
        @endif

        {{-- Secondary tabs partial kept: operations.partials.secondary-tabs --}}
        <div @class(['mt-10' => ! request()->routeIs('operations.job-portal.*', 'operations.pin-codes.*'), 'mt-8' => request()->routeIs('operations.job-portal.*', 'operations.pin-codes.*')])>
            {{ $slot }}
        </div>
    </div>
</x-app-layout>
