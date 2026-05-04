<x-app-layout
    :page-title="__('Operations')"
    :welcome-line="__('Run-state, hiring, coverage, and operational management workspace.')"
>
    <div class="operations-workspace">
        <div class="border-b border-[rgba(255,255,255,0.045)]">
            @include('operations.partials.primary-tabs')
        </div>

        @if (request()->routeIs('operations.job-portal.*', 'operations.pin-codes.*'))
            <div
                class="sticky top-[72px] z-20 -mx-8 border-b border-[rgba(255,255,255,0.045)] bg-[rgba(10,15,28,0.92)] px-8 py-4 shadow-[0_12px_24px_-12px_rgba(0,0,0,0.45)] backdrop-blur-md"
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
