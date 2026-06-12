@php
    use App\Support\ProductCategoryContext;

    /** @var \App\Models\Service $service */
    $isProductCategory = ProductCategoryContext::isService($service);
    $seo = $service->seo;
    $metaDescription = $seo?->meta_description ?: $service->short_summary;
    $featuredSrc = null;
    if (filled($service->featured_image)) {
        $featuredSrc = \Illuminate\Support\Str::startsWith($service->featured_image, ['http://', 'https://'])
            ? $service->featured_image
            : asset('storage/'.$service->featured_image);
    }
@endphp

@extends('layouts.app')

@section('title', ($seo?->meta_title ?: $service->title).' — '.config('medca.brand_name'))

@push('meta')
    @if (! $service->isListedPublicly())
        <meta name="robots" content="noindex, nofollow">
    @endif
    @if (filled($metaDescription))
        <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $metaDescription), 320, '') }}">
    @endif
    <meta property="og:title" content="{{ strip_tags($seo?->meta_title ?: $service->title) }}">
    @if (filled($metaDescription))
        <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $metaDescription), 320, '') }}">
    @endif
    @if ($featuredSrc !== null)
        <meta property="og:image" content="{{ $featuredSrc }}">
        @if (filled($service->image_alt))
            <meta property="og:image:alt" content="{{ strip_tags($service->image_alt) }}">
        @endif
    @endif
@endpush

@section('content')
    <x-public.service-page-hero
        :service="$service"
        :eyebrow="$isProductCategory ? __('Product') : null"
        :headline="$isProductCategory ? ProductCategoryContext::stripServicesLabel(app(\App\Services\Public\PublicDisplayNameResolver::class)->serviceHeadline($service)) : null"
        tone="brand"
        data-service-detail-hero
    />

    <x-public.service-detail-body :service="$service" class="px-4 py-10 sm:px-6 lg:px-8" />

    @if ($service->pincodes->isNotEmpty())
        <div class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
            <x-public.areas-served-grid :areas="$service->pincodes" :service="$service" class="rounded-xl border border-slate-200 bg-slate-50 p-5 md:p-6" />
        </div>
    @endif

    <div class="mx-auto w-full max-w-6xl px-4 pb-10 pt-4 sm:px-6 lg:px-8">
        <x-public.service-internal-links :links="$internalLinks ?? []" />
    </div>
@endsection
