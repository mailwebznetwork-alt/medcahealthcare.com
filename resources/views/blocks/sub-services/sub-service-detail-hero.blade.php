@php
    use App\Services\Public\PublicDisplayNameResolver;

    $sub = $subService ?? null;
    $parent = $sub?->service;
    $headline = $sub ? app(PublicDisplayNameResolver::class)->subServiceHeadline($sub) : '';
    $summary = $sub?->short_summary ?: $sub?->description ?: $sub?->seo?->meta_description;
    $eyebrow = $parent?->title ?: __('Sub-service');
@endphp
@if ($sub)
    <x-public.location-page-hero
        :eyebrow="$eyebrow"
        :headline="$headline"
        :intro="$summary"
        :show-country="false"
        :show-actions="true"
        :show-body="false"
        tone="brand"
    />
@endif
