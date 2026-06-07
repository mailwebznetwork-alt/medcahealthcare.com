@props([
    'pageTitle' => null,
    'welcomeLine' => null,
    'contentClass' => null,
    'breadcrumbs' => [],
])

@php
    $hasToolbar = isset($toolbar);
    $resolvedContentClass = $contentClass ?? ($hasToolbar ? 'mt-8' : 'mt-10');
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
            <div class="mom-backend-toolbar-row mom-sticky-toolbar sticky top-[72px] z-20 -mx-8 px-8 py-3.5">
                {{ $toolbar }}
            </div>
        @endisset

        <div {{ $attributes->class([$resolvedContentClass]) }}>
            {{ $slot }}
        </div>
    </div>
    @livewire('media.media-picker-modal')
</x-app-layout>
