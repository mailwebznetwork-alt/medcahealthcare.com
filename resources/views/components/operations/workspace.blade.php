<x-app-layout
    :page-title="__('Operations')"
    :welcome-line="__('Run-state, hiring, coverage, and operational management workspace.')"
>
    <div class="operations-workspace">
        @include('operations.partials.primary-tabs')
        <div class="mt-0 border-b border-[rgba(255,255,255,0.045)]">
            @include('operations.partials.secondary-tabs')
        </div>
        <div class="mt-10">
            {{ $slot }}
        </div>
    </div>
</x-app-layout>
