@extends('layouts.app')

@section('title', __('Healthcare Services in :area', ['area' => $area]).' — '.config('medca.brand_name'))

@push('meta')
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(__('Professional healthcare services available in :area (:pin).', ['area' => $area, 'pin' => $pin->pincode]), 320, '') }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
@endpush

@section('content')
    <div class="mx-auto w-full max-w-6xl px-4 pt-6 sm:px-6 lg:px-8 md:pt-8">
        <x-public.breadcrumbs :items="$breadcrumbs" />
    </div>
    @php
        $title = __('Healthcare Services in :area', ['area' => $area]);
        $intro = __('Professional healthcare services available in :area (:pin).', [
            'area' => $area,
            'pin' => $pin->pincode,
        ]);
    @endphp

    <x-public.location-page-hero
        :eyebrow="__('Near You')"
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
                :section-title="__('Healthcare services in your area')"
            />

            <x-public.locations-coverage-grid
                :areas="$coverageAreas"
                :exclude-pincode-ids="[$pin->id]"
                :title="__('Areas we cover')"
                :initial="8"
            />

            <x-public.location-about-coverage :pin="$pin" />

            <x-public.location-local-faq :pin="$pin" />

            <x-public.location-area-cta />
        </div>
    </x-public.section>
@endsection
