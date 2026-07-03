@extends('layouts.app')

@section('content')
    <x-public.location-page-hero
        :eyebrow="__('Site map')"
        :headline="__('Sitemap')"
        :subline="__('Browse all public services, locations, pages, and articles.')"
        :show-pincode="false"
        :show-actions="false"
        :show-body="false"
        tone="brand"
    />

    <x-public.section class="bg-white">

    <form method="get" action="{{ route('public.sitemap.html') }}" class="mt-6 max-w-md">
        <label for="sitemap-q" class="sr-only">{{ __('Search sitemap') }}</label>
        <input id="sitemap-q" type="search" name="q" value="{{ $search }}" placeholder="{{ __('Search…') }}" class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm" />
    </form>

    <div class="mt-10 grid gap-10 md:grid-cols-2">
        <section>
            <h2 class="text-lg font-semibold text-medca-primary">{{ __('Services') }} ({{ $services->count() }})</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($services as $service)
                    <li><a href="{{ $service->publicUrl() }}" class="text-clinical-700 hover:underline">{{ $service->title }}</a></li>
                @endforeach
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-medca-primary">{{ __('Locations') }} ({{ $locationsTotal }})</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($locations as $row)
                    <li>
                        <a href="{{ $row->publicUrl() }}" class="text-clinical-700 hover:underline">
                            @php
                                $row->loadMissing(['service', 'pincode']);
                                $sitemapLocationTitle = ($row->service && $row->pincode)
                                    ? app(\App\Services\Public\PublicDisplayNameResolver::class)->locationHeadline($row->service, $row->pincode)
                                    : ($row->service?->title ?? $row->page?->title);
                            @endphp
                            {{ $sitemapLocationTitle }}
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($locationPages > 1)
                <nav class="mt-4 flex flex-wrap items-center gap-2 text-sm" aria-label="{{ __('Location pages') }}">
                    @if ($locationPage > 1)
                        <a href="{{ route('public.sitemap.html', array_filter(['q' => $search ?: null, 'location_page' => $locationPage - 1])) }}" class="rounded border border-slate-200 px-3 py-1 text-clinical-700 hover:bg-slate-50">{{ __('Previous') }}</a>
                    @endif
                    <span class="text-slate-600">{{ __('Page :current of :total', ['current' => $locationPage, 'total' => $locationPages]) }}</span>
                    @if ($locationPage < $locationPages)
                        <a href="{{ route('public.sitemap.html', array_filter(['q' => $search ?: null, 'location_page' => $locationPage + 1])) }}" class="rounded border border-slate-200 px-3 py-1 text-clinical-700 hover:bg-slate-50">{{ __('Next') }}</a>
                    @endif
                </nav>
            @endif
        </section>

        <section>
            <h2 class="text-lg font-semibold text-medca-primary">{{ __('Web pages') }} ({{ $webPages->count() }})</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($webPages as $page)
                    <li><a href="{{ $page->publicUrl() }}" class="text-clinical-700 hover:underline">{{ $page->title }}</a></li>
                @endforeach
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-medca-primary">{{ __('Blogs') }} ({{ $blogs->count() }})</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($blogs as $blog)
                    <li><a href="{{ url('/blog/'.$blog->slug) }}" class="text-clinical-700 hover:underline">{{ $blog->title }}</a></li>
                @endforeach
            </ul>
        </section>

        @if ($landingPages->isNotEmpty())
        <section class="md:col-span-2">
            <h2 class="text-lg font-semibold text-medca-primary">{{ __('Landing pages') }}</h2>
            <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-sm">
                @foreach ($landingPages as $page)
                    <li><a href="{{ $page->publicUrl() }}" class="text-clinical-700 hover:underline">{{ $page->title }}</a></li>
                @endforeach
            </ul>
        </section>
        @endif
    </div>
    </x-public.section>
@endsection
