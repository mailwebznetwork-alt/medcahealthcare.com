<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="medca-public-root">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="{{ config('medca.theme_color') }}">
        <style>
            [x-cloak]{display:none!important}
            html.medca-public-root,
            body.medca-public-surface {
                overflow-x: clip;
                max-width: 100%;
            }
        </style>
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
        @php
            $documentMeta = app(\App\Services\Public\CatalogPublicCache::class)->documentMeta(
                $page ?? null,
                $service ?? null,
                $category ?? null,
                $serviceLocation ?? null,
                $subService ?? null,
            );
        @endphp
        @include('global.partials.site-seo-meta')
        @includeWhen(isset($page), 'global.partials.page-json-ld')
        @php
            $themeResolver = app(\App\Services\Theme\ThemeResolver::class);
            $themeBranding = $themeResolver->branding();
            $faviconUrl = app(\App\Services\Theme\ThemeConfigRepository::class)->assetUrl($themeBranding['favicon_path'] ?? null)
                ?: asset('favicon.ico');
        @endphp
        <link rel="icon" href="{{ $faviconUrl }}">
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
                {{ $documentMeta['meta_title'] }} — {{ config('medca.brand_name') }}
            @elseif(isset($category))
                {{ $documentMeta['meta_title'] }} — {{ config('medca.brand_name') }}
            @elseif(isset($vacancy))
                {{ $vacancy->seo_title ?: $vacancy->title }} — {{ config('medca.brand_name') }}
            @elseif(isset($page))
                {{ $documentMeta['meta_title'] }} — {{ config('medca.brand_name') }}
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
            $layoutMainClass = $themeResolver->layoutMainClasses().' pb-8 md:pb-10 lg:pb-12 pt-0';
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
                @php
                    $renderCtx = app(\App\Services\Content\ContentRenderContext::class)->all();
                    $hasBreadcrumbData = ! empty($breadcrumbs ?? null)
                        || ! empty($renderCtx['breadcrumbs'] ?? null);
                    $showGrowthChrome = $hasBreadcrumbData
                        && ! config('medca.hide_visual_breadcrumbs', true);
                @endphp
                @if ($showGrowthChrome)
                    <div class="w-full">
                        @include('public.partials.growth-chrome')
                    </div>
                @endif
                <div @class(['w-full', 'pb-6 md:pb-8' => ! $page->usesCanvasLayout()])>
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
                @if ($page->slug === 'home')
                    @php
                        $pageContent = (string) ($page->content ?? '');
                        $reviewsTokenMissing = ! preg_match('/\{\{\s*block\s*:\s*reviews[\w-]*\s*\}\}/', $pageContent);
                    @endphp
                    @if ($reviewsTokenMissing)
                        @include('public.partials.home-trust-reviews')
                    @endif
                @endif
                @include('public.partials.page-related-bottom')
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
                    <div class="max-w-none pb-6 md:pb-8 pt-0">
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
