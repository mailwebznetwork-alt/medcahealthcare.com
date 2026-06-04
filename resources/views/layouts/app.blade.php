<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="medca-public-root">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="{{ config('medca.theme_color') }}">
        <style>[x-cloak]{display:none!important}</style>
        @stack('meta')
        @isset($vacancy)
            @php
                $vacancyMetaTitle = $vacancy->seo_title ?: $vacancy->title;
                $vacancyMetaDescription = $vacancy->seo_description ?: \Illuminate\Support\Str::limit(strip_tags((string) ($vacancy->summary ?: $vacancy->description)), 160);
            @endphp
            <meta name="description" content="{{ $vacancyMetaDescription }}">
            @if ($vacancy->focus_keywords)
                <meta name="keywords" content="{{ $vacancy->focus_keywords }}">
            @endif
            <link rel="canonical" href="{{ url()->current() }}">
        @endisset
        @isset($service)
            @unless (isset($page))
                @php
                    $serviceMetaTitle = $service->seo?->meta_title ?: $service->title;
                    $serviceMetaDescription = $service->seo?->meta_description ?: $service->short_summary;
                @endphp
                @if (filled($serviceMetaDescription))
                    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $serviceMetaDescription), 320, '') }}">
                @endif
                <link rel="canonical" href="{{ $service->publicUrl() }}">
            @endunless
        @endisset
        @if (isset($service) && ! $service->isListedPublicly())
            <meta name="robots" content="noindex, nofollow">
        @endif
        @include('global.partials.site-seo-meta')
        @includeWhen(isset($page), 'global.partials.page-json-ld')
        @php
            $themeResolver = app(\App\Services\Theme\ThemeResolver::class);
            $themeBranding = $themeResolver->branding();
            $faviconUrl = app(\App\Services\Theme\ThemeConfigRepository::class)->assetUrl($themeBranding['favicon_path'] ?? null);
        @endphp
        @if ($faviconUrl)
            <link rel="icon" href="{{ $faviconUrl }}">
        @endif
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="{{ $themeResolver->googleFontsHref() }}" rel="stylesheet">
        @vite(['resources/css/public/public.css', 'resources/js/app.js'])
        <x-theme.public-vars />
        <x-marketing.tracking-head :settings="$marketingSettings ?? null" />
        @if (isset($page) && filled($page->gtm_code))
            {!! $page->gtm_code !!}
        @endif
        @stack('schema')
        @if (isset($vacancy) && isset($jobPostingSchema) && $jobPostingSchema !== [])
            <script type="application/ld+json">{!! json_encode($jobPostingSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endif
        <title>
            @isset($service)
                @if (isset($page) && filled($page->meta_title))
                    {{ $page->meta_title }} — {{ config('medca.brand_name') }}
                @elseif (isset($page))
                    {{ $page->title }} — {{ config('medca.brand_name') }}
                @else
                    {{ $service->seo?->meta_title ?: $service->title }} — {{ config('medca.brand_name') }}
                @endif
            @elseif(isset($vacancy))
                {{ $vacancy->seo_title ?: $vacancy->title }} — {{ config('medca.brand_name') }}
            @elseif(isset($page))
                {{ $page->meta_title ?? $page->title }} — {{ config('medca.brand_name') }}
            @elseif(isset($blog))
                {{ $blog->meta_title ?? $blog->title }} — {{ config('medca.brand_name') }}
            @else
                @yield('title', config('medca.brand_name'))
            @endisset
        </title>
    </head>
    <body class="medca-public-surface flex min-h-screen flex-col bg-slate-50 font-medca-sans antialiased text-slate-800">
        @include('global.header')

        @php
            $layoutMainClass = $themeResolver->layoutMainClasses().' py-8 md:py-10 lg:py-12';
        @endphp
        <main
            id="main-content"
            @class([
                'relative z-0 flex-1 w-full',
                $layoutMainClass => ! isset($page) && ! isset($blog),
                $layoutMainClass => isset($page) && ! $page->usesCanvasLayout(),
                'px-0 py-0' => isset($page) && $page->usesCanvasLayout(),
                $layoutMainClass => isset($blog),
            ])
        >
            @isset($page)
                <div @class(['w-full', 'py-6 md:py-8' => ! $page->usesCanvasLayout()])>
                    {!! \App\Services\ContentParser::parse($page->content ?? '') !!}
                </div>
                @php
                    $nearYouFallback = match ($page->slug) {
                        'home' => ['block' => 'near-you-home', 'partial' => true],
                        'locations' => ['block' => 'near-you-locations', 'partial' => true],
                        default => null,
                    };
                    $nearYouTokenMissing = $nearYouFallback !== null
                        && ! preg_match('/\{\{\s*block\s*:\s*near-you[\w-]*\s*\}\}/', (string) ($page->content ?? ''));
                @endphp
                @if ($nearYouTokenMissing)
                    @include('public.partials.near-you-services', array_merge(
                        app(\App\Services\Public\PublicPagePresenter::class)->nearYouPayload(),
                        ['contentSlug' => $nearYouFallback['block']]
                    ))
                @endif
            @elseif(isset($blog))
                <article class="w-full">
                    @if ($blog->featured_image)
                        <div class="mb-8 overflow-hidden rounded-lg border border-slate-200 shadow-sm">
                            <img
                                src="{{ \Illuminate\Support\Str::startsWith($blog->featured_image, ['http://', 'https://']) ? $blog->featured_image : asset('storage/'.$blog->featured_image) }}"
                                alt=""
                                class="max-h-[28rem] w-full object-cover"
                                loading="lazy"
                            />
                        </div>
                    @endif
                    <div class="max-w-none py-6 md:py-8">
                        {!! \App\Services\ContentParser::parse($blog->content ?? '') !!}
                    </div>
                </article>
            @else
                @yield('content')
            @endisset
        </main>

        @include('global.footer')
        @livewire('location.pincode-modal')
        @include('global.floating')
        <x-marketing.tracking-body :settings="$marketingSettings ?? null" />
        <x-marketing.tracking-events />
        @stack('scripts')
    </body>
</html>
