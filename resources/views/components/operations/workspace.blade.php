@push('mom-module-toolbar')
    <div class="mom-module-toolbar-host">
        <div class="mom-module-toolbar-host__inner flex flex-col">
            <div class="border-b border-[var(--border-soft)] pb-2 pt-3">
                @include('operations.partials.primary-tabs')
            </div>
            @if (request()->routeIs('operations.job-portal.*'))
                <div class="py-4">
                    @include('operations.job-portal.partials.toolbar')
                </div>
            @elseif (request()->routeIs('operations.pin-codes.*'))
                <div class="py-4">
                    @include('operations.pin-codes.partials.toolbar')
                </div>
            @endif
        </div>
    </div>
@endpush

<x-app-layout
    :page-title="__('Operations')"
    :welcome-line="__('Run-state, hiring, coverage, and operational management workspace.')"
>
    <div class="operations-workspace">
        <div>
            {{ $slot }}
        </div>
    </div>
</x-app-layout>
