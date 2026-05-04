@props([
    'pageTitle' => null,
    'welcomeLine' => null,
])

@php
    $resolvedPageTitle = $pageTitle ?? __('Site Architect');
    $resolvedWelcome = $welcomeLine ?? __('Structure-only content, reusable blocks, PIN-code GEO.');
@endphp

<x-app-layout
    :page-title="$resolvedPageTitle"
    :welcome-line="$resolvedWelcome"
>
    <div class="operations-workspace">
        <div class="mom-backend-tabstrip">
            @include('site-architect.partials.primary-tabs')
        </div>

        <div class="mt-10">
            {{ $slot }}
        </div>
    </div>
</x-app-layout>
