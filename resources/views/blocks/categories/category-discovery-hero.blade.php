@php
    use App\Services\Public\PublicDisplayNameResolver;

    $category = $category ?? ($serviceCategory ?? null);
    $displayNames = app(PublicDisplayNameResolver::class);
    $headline = $category ? $displayNames->categoryHeadline($category) : '';
    $summary = $category ? ($displayNames->categoryMetaDescription($category) ?: $category->description) : '';
@endphp
@if ($category)
    <x-public.location-page-hero
        :eyebrow="__('Category')"
        :headline="$headline"
        :subline="$summary"
        :show-pincode="false"
        :show-actions="true"
        :show-body="false"
        tone="brand"
    />
@endif
