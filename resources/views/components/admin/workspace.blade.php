@props([
    'pageTitle' => null,
    'welcomeLine' => null,
    'contentClass' => null,
    'breadcrumbs' => [],
])

@php
    $hasToolbar = isset($toolbar);
    $resolvedContentClass = $contentClass ?? ($hasToolbar ? 'mt-0' : 'mt-10');
@endphp

<x-app-layout
    :page-title="$pageTitle"
    :welcome-line="$welcomeLine"
>
    <div class="operations-workspace">
        @if (! empty($breadcrumbs))
            <x-admin.breadcrumb :items="$breadcrumbs" />
        @endif

        @isset($tabs)
            <div class="mom-backend-tabstrip">
                {{ $tabs }}
            </div>
        @endisset

        @isset($toolbar)
            <div class="mom-backend-toolbar-row mom-sticky-toolbar sticky top-0 z-20 -mx-4 mb-6 border-b border-[var(--border-tabstrip-divider)] bg-[var(--bg-app)] px-4 py-3.5 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                {{ $toolbar }}
            </div>
        @endisset

        <div {{ $attributes->class([$resolvedContentClass]) }}>
            {{ $slot }}
        </div>
    </div>
    @livewire('media.media-picker-modal')
</x-app-layout>
