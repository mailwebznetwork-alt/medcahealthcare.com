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

@section('title', ($seo?->meta_title ?: $service->title).' — '.config('app.name'))

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
    <article class="space-y-8 py-6 md:py-8">
        <header class="space-y-4">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ __('Service') }}</p>
            <h1 class="text-3xl font-semibold leading-tight text-slate-900 md:text-4xl">{{ $heading }}</h1>
            @if (filled($service->short_summary))
                <p class="max-w-3xl text-base text-slate-600 md:text-lg">{{ $service->short_summary }}</p>
            @endif
            @if ($service->hasPriceRange())
                <p class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-slate-200">
                    <span class="font-semibold">{{ __('Pricing') }}:</span> {{ $service->price_range }}
                </p>
            @endif
        </header>

        @if ($featuredSrc !== null)
            <figure class="overflow-hidden rounded-lg border border-slate-200 shadow-sm">
                <img
                    src="{{ $featuredSrc }}"
                    alt="{{ $service->image_alt ?? $service->title }}"
                    class="max-h-[28rem] w-full object-cover"
                    loading="lazy"
                />
            </figure>
        @endif

        @if (filled($service->description))
            <div class="prose prose-slate max-w-none">
                {!! $service->description !!}
            </div>
        @endif

        @if ($service->faqs->isNotEmpty())
            <section class="space-y-4">
                <h2 class="text-2xl font-semibold text-slate-900">{{ __('Frequently asked questions') }}</h2>
                <dl class="space-y-4">
                    @foreach ($service->faqs as $faq)
                        @if (trim((string) $faq->question) !== '' && trim((string) $faq->answer) !== '')
                            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                                <dt class="text-base font-semibold text-slate-900">{{ $faq->question }}</dt>
                                <dd class="mt-2 text-sm leading-6 text-slate-600">{{ $faq->answer }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </section>
        @endif

        @if ($service->pincodes->isNotEmpty())
            <section class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-5">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('Areas served') }}</h2>
                <ul class="flex flex-wrap gap-2 text-sm text-slate-700">
                    @foreach ($service->pincodes as $pc)
                        <li class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">
                            <span class="font-mono text-slate-500">{{ $pc->pincode }}</span>
                            <span class="ml-1">{{ $pc->area_name }}, {{ $pc->city }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>
@endsection
