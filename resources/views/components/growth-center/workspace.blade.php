@props([
    'pageTitle' => null,
    'welcomeLine' => null,
    'activeTab' => null,
])

@php
    $resolvedPageTitle = $pageTitle ?? __('Growth Center');
    $resolvedWelcome = $welcomeLine ?? __('Competitor intelligence, SEO, coverage, and analytics.');
    $tab = $activeTab ?? (string) request()->query('tab', 'competitors');
    $tabLabels = [
        'readiness' => __('Readiness'),
        'competitors' => __('Competitors'),
        'war-room' => __('War Room'),
        'hijack-opportunities' => __('Hijack Ops'),
        'seo' => __('SEO'),
        'ga4' => __('GA4'),
        'ai-pulse' => __('AI Pulse'),
    ];
    $breadcrumbs = [
        ['label' => __('Growth Center'), 'url' => route('growth-center.competitors.index')],
        ['label' => $tabLabels[$tab] ?? ucfirst(str_replace('-', ' ', $tab)), 'url' => null],
    ];
@endphp

<x-admin.workspace
    :page-title="$resolvedPageTitle"
    :welcome-line="$resolvedWelcome"
    :breadcrumbs="$breadcrumbs"
>
    <x-slot:tabs>
        @include('growth-center.partials.primary-tabs', ['activeTab' => $tab])
    </x-slot:tabs>

    {{ $slot }}
</x-admin.workspace>
