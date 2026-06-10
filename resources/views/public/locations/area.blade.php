@extends('layouts.app')

@section('title', $title.' — '.config('medca.brand_name'))

@push('meta')
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($intro, 320, '') }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
@endpush

@section('content')
    @if (! config('medca.hide_visual_breadcrumbs', true) && ! empty($breadcrumbs))
        <div class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
            <x-public.breadcrumbs :items="$breadcrumbs" />
        </div>
    @endif

    <x-public.location-page-hero
        :eyebrow="$category ? __('Category') : ($contextService ? __('Service') : __('Near You'))"
        :headline="$title"
        :subline="$intro"
        :intro="null"
        :show-body="false"
        tone="brand"
    />

    <x-public.section>
        <div class="medca-location-area space-y-10" data-location-area="{{ $pin->pincode }}">
            <x-public.location-services-detail-list
                :services="$services"
                :pin-code-record="$pin"
                :empty-message="$category
                    ? __('No published :category services are mapped to this pincode yet.', ['category' => $category->name])
                    : ($contextService
                        ? __('This service is not mapped to this pincode yet.')
                        : __('No published services are mapped to this pincode yet.'))"
            />

            @if ($showNearYouBlock ?? false)
                @include('public.partials.near-you-services', array_merge(
                    $nearYouPayload ?? app(\App\Services\Public\PublicPagePresenter::class)->nearYouPayload(),
                    ['contentSlug' => 'near-you-locations']
                ))
            @endif

            <x-public.locations-coverage-grid
                :areas="$coverageAreas"
                :exclude-pincode-ids="[$pin->id]"
                :category="$category ?? null"
                :service="$contextService ?? null"
                :title="__('Areas we cover')"
                :initial="8"
            />

            <x-public.location-about-coverage :pin="$pin" />

            <x-public.location-local-faq :pin="$pin" />

            <x-public.location-area-cta />
        </div>
    </x-public.section>
@endsection
