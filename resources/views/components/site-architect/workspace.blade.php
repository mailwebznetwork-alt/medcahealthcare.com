@props([
    'pageTitle' => null,
    'welcomeLine' => null,
])

@php
    $resolvedPageTitle = $pageTitle ?? __('Site Architect');
    $resolvedWelcome = $welcomeLine ?? \App\Support\SiteArchitectUxCopy::workspaceWelcome();
    $showComposeJourney = request()->routeIs(
        'site-architect.pages.*',
        'site-architect.block-studio.*',
        'site-architect.block-factory.*',
        'site-architect.block-presets.*',
        'site-architect.presets.*',
    );
@endphp

<x-admin.workspace
    :page-title="$resolvedPageTitle"
    :welcome-line="$resolvedWelcome"
>
    <x-slot:tabs>
        @include('site-architect.partials.primary-tabs')
    </x-slot:tabs>

    @if ($showComposeJourney)
        @include('site-architect.partials.compose-journey', ['compact' => ! request()->routeIs('site-architect.pages.index')])
    @endif

    {{ $slot }}
</x-admin.workspace>
