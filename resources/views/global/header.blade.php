{{-- Medca public marketing chrome (reference: live Medca header — top bar, wordmark, full nav). --}}
@php
    $navAccent = 'text-[#6f42c1]';
    $navMuted = 'text-slate-700';
    $navBase = 'text-[11px] font-bold uppercase tracking-widest transition';
    $isHome = request()->path() === '/' || request()->path() === '';
    $isCareers = request()->routeIs('careers.*');
@endphp

<header x-data="{ mobileMenuOpen: false }" class="relative z-[999] w-full bg-white shadow-sm">
    <div class="bg-[#002366] px-4 py-2 text-[11px] text-white sm:px-6">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2">
            <span class="font-medium">{{ config('medca.top_bar_claim') }}</span>
            <span class="inline-flex items-center gap-1.5 text-slate-100">
                <svg class="h-3.5 w-3.5 shrink-0 opacity-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ config('medca.location_display') }}</span>
            </span>
        </div>
    </div>

    <div class="border-b border-[#eeeeee]">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
            <a
                href="{{ url('/') }}"
                class="inline-flex min-w-0 shrink-0 items-center gap-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#6f42c1]/40"
                aria-label="{{ config('medca.brand_name') }} — {{ __('Home') }}"
            >
                <img
                    src="{{ asset('images/medca-logo.png') }}"
                    alt="{{ config('medca.brand_name') }}"
                    width="200"
                    height="56"
                    class="h-9 w-auto max-w-[min(11rem,50vw)] object-contain"
                    loading="eager"
                    decoding="async"
                />
                <span class="hidden min-w-0 sm:block">
                    <span class="block truncate text-base font-semibold text-[#002366]">{{ config('medca.brand_name') }}</span>
                    <span class="block truncate text-[10px] font-semibold uppercase tracking-[0.28em] text-[#4a6fa8]">{{ mb_strtoupper(config('medca.tagline')) }}</span>
                </span>
            </a>

            <nav class="hidden items-center space-x-6 lg:flex" aria-label="{{ __('Primary') }}">
                <a href="{{ url('/') }}" @class([$navBase, $isHome ? $navAccent : $navMuted, 'hover:text-[#6f42c1]' => ! $isHome])>{{ __('Home') }}</a>
                <a href="{{ url('/#about') }}" @class([$navBase, $navMuted, 'hover:text-[#6f42c1]'])>{{ __('About') }}</a>
                <a href="{{ url('/#services') }}" @class([$navBase, $navMuted, 'hover:text-[#6f42c1]'])>{{ __('Services') }}</a>
                <a href="{{ url('/#locations') }}" @class([$navBase, $navMuted, 'hover:text-[#6f42c1]'])>{{ __('Locations') }}</a>
                <a href="{{ route('careers.index') }}" @class([$navBase, $isCareers ? $navAccent : $navMuted, 'hover:text-[#6f42c1]' => ! $isCareers])>{{ __('Careers') }}</a>
                <a href="{{ url('/#contact') }}" @class([$navBase, $navMuted, 'hover:text-[#6f42c1]'])>{{ __('Contact') }}</a>
            </nav>

            <div class="flex items-center space-x-3">
                <a
                    href="{{ url('/#callback') }}"
                    class="hidden rounded bg-[#83b735] px-3 py-2 text-[11px] font-bold text-white shadow-sm transition hover:brightness-105 sm:inline-block"
                >
                    {{ __('Book Callback') }}
                </a>
                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="rounded p-1 text-slate-700 lg:hidden"
                    :aria-expanded="mobileMenuOpen"
                    aria-controls="medca-mobile-nav"
                    aria-label="{{ __('Toggle menu') }}"
                >
                    <span class="sr-only">{{ __('Toggle menu') }}</span>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div
        x-show="mobileMenuOpen"
        x-cloak
        x-transition
        id="medca-mobile-nav"
        class="space-y-2 border-t border-[#eeeeee] bg-white p-4 shadow-inner lg:hidden"
    >
        <a href="{{ url('/') }}" class="block text-[11px] font-bold uppercase tracking-wide {{ $isHome ? 'text-[#6f42c1]' : 'text-slate-800' }}" @click="mobileMenuOpen = false">{{ __('Home') }}</a>
        <a href="{{ url('/#about') }}" class="block text-[11px] font-bold uppercase tracking-wide text-slate-800" @click="mobileMenuOpen = false">{{ __('About') }}</a>
        <a href="{{ url('/#services') }}" class="block text-[11px] font-bold uppercase tracking-wide text-slate-800" @click="mobileMenuOpen = false">{{ __('Services') }}</a>
        <a href="{{ url('/#locations') }}" class="block text-[11px] font-bold uppercase tracking-wide text-slate-800" @click="mobileMenuOpen = false">{{ __('Locations') }}</a>
        <a href="{{ route('careers.index') }}" class="block text-[11px] font-bold uppercase tracking-wide {{ $isCareers ? 'text-[#6f42c1]' : 'text-slate-800' }}" @click="mobileMenuOpen = false">{{ __('Careers') }}</a>
        <a href="{{ url('/#contact') }}" class="block text-[11px] font-bold uppercase tracking-wide text-slate-800" @click="mobileMenuOpen = false">{{ __('Contact') }}</a>
        <a
            href="{{ url('/#callback') }}"
            class="mt-3 block rounded bg-[#83b735] px-4 py-2 text-center text-xs font-bold text-white shadow-sm"
            @click="mobileMenuOpen = false"
        >
            {{ __('Book Callback') }}
        </a>
    </div>
</header>
