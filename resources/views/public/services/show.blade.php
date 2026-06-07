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
    <x-public.service-page-hero :service="$service" tone="brand" data-service-detail-hero />

    <article class="medca-service-detail mx-auto w-full max-w-6xl space-y-10 px-4 sm:px-6 lg:px-8">
        @if (($averageRating ?? null) !== null && ($reviewsCount ?? 0) > 0)
            <p class="text-sm font-medium text-amber-700 md:text-base">
                {{ __(':rating / 5 · :count reviews', ['rating' => number_format((float) $averageRating, 1), 'count' => (int) $reviewsCount]) }}
            </p>
        @endif

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

        @php
            $approvedReviews = $service->approvedReviews()->latest()->limit(3)->get();
        @endphp
        @if ($approvedReviews->isNotEmpty())
            <section class="space-y-4">
                <h2 class="text-2xl font-semibold text-slate-900 md:text-3xl">{{ __('Patient reviews') }}</h2>
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach ($approvedReviews as $review)
                        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-amber-500">{{ str_repeat('★', min(5, (int) $review->rating)) }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ \Illuminate\Support\Str::limit((string) $review->comment, 200) }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
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
            <x-public.areas-served-grid :areas="$service->pincodes" :service="$service" class="rounded-xl border border-slate-200 bg-slate-50 p-5 md:p-6" />
        @endif

        <section>
            @livewire('reviews.review-form', ['serviceId' => $service->id], key('review-form-'.$service->id))
        </section>

        <x-public.service-internal-links :links="$internalLinks ?? []" />
    </article>
@endsection
