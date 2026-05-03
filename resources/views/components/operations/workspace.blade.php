<x-app-layout
    :page-title="__('Operations')"
    :welcome-line="__('Run-state, hiring, coverage, and operational management workspace.')"
>
    <div class="operations-workspace">
        <div class="border-b border-[rgba(255,255,255,0.045)]">
            @include('operations.partials.primary-tabs')
        </div>
        {{-- Secondary tabs partial kept: operations.partials.secondary-tabs --}}
        <div class="mt-10">
            {{ $slot }}
        </div>
    </div>
</x-app-layout>
