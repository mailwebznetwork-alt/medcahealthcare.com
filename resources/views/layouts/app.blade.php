<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <style>[x-cloak]{display:none!important}</style>
        @stack('meta')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=noto-sans:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('schema')
        <title>@yield('title', config('app.name'))</title>
    </head>
    <body class="flex min-h-screen flex-col bg-[var(--bg-app)] font-sans antialiased text-[var(--text-primary)]">
        @include('global.header')

        <main id="main-content" class="relative z-0 flex-1">
            @isset($page)
                <div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
                    {!! \App\Services\ContentParser::parse($page->content ?? '') !!}
                </div>
            @else
                @yield('content')
            @endisset
        </main>

        @include('global.footer')
        @include('global.floating')
        @stack('scripts')
    </body>
</html>
