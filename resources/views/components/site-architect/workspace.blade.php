@props([
    'pageTitle' => null,
    'welcomeLine' => null,
])

@php
    $resolvedPageTitle = $pageTitle ?? __('Site Architect');
    $resolvedWelcome = $welcomeLine ?? \App\Support\SiteArchitectUxCopy::workspaceWelcome();
@endphp

<x-app-layout
    :page-title="$resolvedPageTitle"
    :welcome-line="$resolvedWelcome"
>
    <div class="site-architect-workspace min-w-0">
        {{ $slot }}
    </div>

    @livewire('media.media-picker-modal')
</x-app-layout>
