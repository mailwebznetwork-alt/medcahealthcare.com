{{-- Matches medca-healthcare `partials/medca-premium-header.blade.php` (public marketing chrome only). --}}
<header x-data="{ mobileMenuOpen: false }" class="relative z-[999] w-full border-b border-slate-200 bg-white shadow-sm">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
        <a
            href="{{ url('/') }}"
            class="inline-flex shrink-0 items-center focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0046ad]/40"
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
        </a>
        <nav class="hidden items-center space-x-6 text-[11px] font-bold uppercase tracking-widest text-slate-600 lg:flex" aria-label="{{ __('Primary') }}">
            <a href="{{ url('/') }}" class="transition hover:text-[#0046ad]">{{ __('Home') }}</a>
            <a href="{{ url('/#about') }}" class="transition hover:text-[#0046ad]">{{ __('About Us') }}</a>
            <a href="{{ url('/#services') }}" class="transition hover:text-[#0046ad]">{{ __('Services') }}</a>
            <a href="{{ url('/#locations') }}" class="transition hover:text-[#0046ad]">{{ __('Locations') }}</a>
            <a href="{{ url('/#contact') }}" class="transition hover:text-[#0046ad]">{{ __('Contact') }}</a>
        </nav>
        <div class="flex items-center space-x-4">
            <a
                href="{{ url('/#callback') }}"
                class="rounded bg-[#83b735] px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:brightness-105"
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
    <div
        x-show="mobileMenuOpen"
        x-cloak
        x-transition
        id="medca-mobile-nav"
        class="space-y-2 border-t border-slate-200 bg-white p-4 shadow-inner lg:hidden"
    >
        <a href="{{ url('/') }}" class="block font-bold uppercase tracking-wide text-slate-800" @click="mobileMenuOpen = false">{{ __('Home') }}</a>
        <a href="{{ url('/#about') }}" class="block font-bold uppercase tracking-wide text-slate-800" @click="mobileMenuOpen = false">{{ __('About') }}</a>
        <a href="{{ url('/#services') }}" class="block font-bold uppercase tracking-wide text-slate-800" @click="mobileMenuOpen = false">{{ __('Services') }}</a>
        <a href="{{ url('/#locations') }}" class="block font-bold uppercase tracking-wide text-slate-800" @click="mobileMenuOpen = false">{{ __('Locations') }}</a>
        <a href="{{ url('/#contact') }}" class="block font-bold uppercase tracking-wide text-slate-800" @click="mobileMenuOpen = false">{{ __('Contact') }}</a>
        <a
            href="{{ url('/#callback') }}"
            class="mt-3 block rounded bg-[#83b735] px-4 py-2 text-center text-xs font-bold text-white shadow-sm"
            @click="mobileMenuOpen = false"
        >
            {{ __('Book Callback') }}
        </a>
    </div>
</header>
