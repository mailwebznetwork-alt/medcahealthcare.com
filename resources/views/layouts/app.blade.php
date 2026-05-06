<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="medca-public-root">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#002366">
        <style>[x-cloak]{display:none!important}</style>
        @stack('meta')
        @include('global.partials.site-seo-meta')
        @includeWhen(isset($page), 'global.partials.page-json-ld')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <x-marketing.tracking-head :settings="$marketingSettings ?? null" />
        @stack('schema')
        <title>
            @isset($page)
                {{ $page->meta_title ?? $page->title }} — {{ config('app.name') }}
            @elseif(isset($blog))
                {{ $blog->meta_title ?? $blog->title }} — {{ config('app.name') }}
            @else
                @yield('title', config('app.name'))
            @endisset
        </title>
    </head>
    <body class="medca-public-surface flex min-h-screen flex-col bg-slate-50 font-medca-sans antialiased text-slate-800">
        @include('global.header')

        <main id="main-content" class="relative z-0 mx-auto w-full max-w-6xl flex-1 px-4 py-6 md:px-6 lg:px-8">
            @isset($page)
                <div class="w-full py-4 md:py-6">
                    {!! \App\Services\ContentParser::parse($page->content ?? '') !!}
                </div>
            @elseif(isset($blog))
                <article class="w-full py-4 md:py-6">
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
                    <div class="max-w-none">
                        {!! \App\Services\ContentParser::parse($blog->content ?? '') !!}
                    </div>
                </article>
            @else
                @yield('content')
            @endisset
        </main>

        @include('global.footer')
        @include('global.floating')
        <x-marketing.tracking-body :settings="$marketingSettings ?? null" />
        @stack('scripts')
    </body>
</html>
