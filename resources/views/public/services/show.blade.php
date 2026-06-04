@php
    /** @var \App\Models\Service $service */
    $seo = $service->seo;
    $heading = $seo?->h1 ?: $service->title;
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
    <article class="medca-service-detail space-y-10">
        <header class="space-y-4 border-b border-slate-200 pb-8">
            <p class="medca-eyebrow text-slate-500">{{ __('Service') }}</p>
            <h1 class="text-3xl font-semibold leading-tight tracking-tight text-slate-900 md:text-4xl">{{ $heading }}</h1>
            @if (filled($service->short_summary))
                <p class="medca-text-body-lg max-w-3xl text-slate-600">{{ $service->short_summary }}</p>
            @endif
            @if (($averageRating ?? null) !== null && ($reviewsCount ?? 0) > 0)
                <p class="text-sm font-medium text-amber-700 md:text-base">
                    {{ __(':rating / 5 · :count reviews', ['rating' => number_format((float) $averageRating, 1), 'count' => (int) $reviewsCount]) }}
                </p>
            @endif
            @if ($service->hasPriceRange())
                <p class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-slate-200 md:text-base">
                    <span class="font-semibold">{{ __('Pricing') }}:</span> {{ $service->price_range }}
                </p>
            @endif
        </header>

        @if ($featuredSrc !== null)
            <figure class="overflow-hidden rounded-xl border border-slate-200 shadow-sm">
                <img
                    src="{{ $featuredSrc }}"
                    alt="{{ $service->image_alt ?? $service->title }}"
                    class="max-h-[28rem] w-full object-cover"
                    loading="lazy"
                />
            </figure>
        @endif

        @if (filled($service->description))
            <div class="medca-service-prose prose prose-slate max-w-none prose-headings:text-slate-900 prose-p:text-slate-700">
                {!! $service->description !!}
            </div>
        @endif

        @if ($service->faqs->isNotEmpty())
            <section class="space-y-4">
                <h2 class="text-2xl font-semibold text-slate-900 md:text-3xl">{{ __('Frequently asked questions') }}</h2>
                <dl class="space-y-3">
                    @foreach ($service->faqs as $faq)
                        @if (trim((string) $faq->question) !== '' && trim((string) $faq->answer) !== '')
                            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                <dt class="text-base font-semibold text-slate-900 md:text-lg">{{ $faq->question }}</dt>
                                <dd class="mt-2 text-sm leading-relaxed text-slate-600 md:text-base">{{ $faq->answer }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </section>
        @endif

        @if ($service->pincodes->isNotEmpty())
            <section class="medca-service-areas space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-5 md:p-6">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 md:text-xl">{{ __('Areas served') }}</h2>
                    <p class="mt-1 text-sm text-slate-600 md:text-base">{{ __('Bangalore neighbourhoods where this service is available.') }}</p>
                </div>
                <ul class="medca-service-areas__grid">
                    @foreach ($service->pincodes as $pc)
                        <li class="medca-service-areas__item">
                            <span class="font-mono text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $pc->pincode }}</span>
                            <span class="text-sm font-medium leading-snug text-slate-900 md:text-base">{{ $pc->area_name }}</span>
                            @if (filled($pc->city))
                                <span class="text-xs text-slate-500 md:text-sm">{{ $pc->city }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section>
            @livewire('reviews.review-form', ['serviceId' => $service->id], key('review-form-'.$service->id))
        </section>
    </article>
@endsection
