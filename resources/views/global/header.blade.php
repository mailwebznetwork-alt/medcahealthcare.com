@php
    use App\ModuleAccess;

    $nav = [
        ['label' => __('Home'), 'href' => url('/'), 'active' => request()->path() === '/' || request()->path() === ''],
        ['label' => __('About Us'), 'href' => url('/#about'), 'active' => false],
        ['label' => __('Services'), 'href' => url('/#services'), 'active' => false],
        ['label' => __('Locations'), 'href' => url('/#locations'), 'active' => false],
        ['label' => __('Careers'), 'href' => route('careers.index'), 'active' => request()->routeIs('careers.*')],
        ['label' => __('Contact'), 'href' => url('/#contact'), 'active' => false],
    ];
    $navLinkBase = 'text-[11px] font-bold uppercase tracking-widest transition';
    $navLinkIdle = 'text-slate-600 hover:text-[#0046ad]';
    $navLinkActive = 'text-[#0046ad]';
@endphp

<header x-data="{ mobileMenuOpen: false }" class="relative z-[999] w-full border-b border-slate-200 bg-white shadow-sm">
    <div class="bg-[#0f172a] px-4 py-2 text-[11px] text-slate-200 sm:px-6">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2">
            <span class="font-medium text-white">{{ config('medca.top_bar_claim') }}</span>
            <span class="text-slate-300">{{ config('medca.location_display') }}</span>
        </div>
    </div>

    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
        <a href="{{ url('/') }}" class="inline-flex min-w-0 shrink-0 items-center gap-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0046ad]/40" aria-label="{{ config('medca.brand_name') }} — {{ __('Home') }}">
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
                <span class="block truncate text-base font-semibold text-slate-900">{{ config('medca.brand_name') }}</span>
                <span class="block truncate text-xs font-medium tracking-wide text-slate-500">{{ config('medca.tagline') }}</span>
            </span>
        </a>

        <nav class="hidden items-center space-x-6 lg:flex" aria-label="{{ __('Primary') }}">
            @foreach ($nav as $item)
                <a
                    href="{{ $item['href'] }}"
                    @class([$navLinkBase, $item['active'] ? $navLinkActive : $navLinkIdle])
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center space-x-4">
            @auth
                @if (auth()->user()->hasModuleAccess(ModuleAccess::DASHBOARD))
                    <a
                        href="{{ route('dashboard') }}"
                        class="{{ $navLinkBase }} {{ $navLinkIdle }} hidden sm:inline-block"
                    >
                        {{ __('Workspace') }}
                    </a>
                @endif
                @if (auth()->user()->hasModuleAccess(ModuleAccess::GROWTH_CENTER) && Route::has('growth-center.readiness'))
                    <a
                        href="{{ route('growth-center.readiness') }}"
                        class="{{ $navLinkBase }} {{ $navLinkIdle }} hidden md:inline-block"
                    >
                        {{ __('SEO readiness') }}
                    </a>
                @endif
            @elseif (Route::has('login'))
                <a href="{{ route('login') }}" class="{{ $navLinkBase }} {{ $navLinkIdle }} hidden sm:inline-block">
                    {{ __('Log in') }}
                </a>
            @endauth
            <a
                href="{{ url('/#contact') }}"
                class="hidden rounded bg-[#83b735] px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:brightness-105 sm:inline-block"
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
        @foreach ($nav as $item)
            <a
                href="{{ $item['href'] }}"
                @class([
                    'block font-bold uppercase tracking-wide',
                    $item['active'] ? 'text-[#0046ad]' : 'text-slate-800',
                ])
                @click="mobileMenuOpen = false"
            >
                {{ $item['label'] }}
            </a>
        @endforeach
        @auth
            @if (auth()->user()->hasModuleAccess(ModuleAccess::DASHBOARD))
                <a
                    href="{{ route('dashboard') }}"
                    class="block font-bold uppercase tracking-wide text-slate-800"
                    @click="mobileMenuOpen = false"
                >
                    {{ __('Workspace') }}
                </a>
            @endif
            @if (auth()->user()->hasModuleAccess(ModuleAccess::GROWTH_CENTER) && Route::has('growth-center.readiness'))
                <a
                    href="{{ route('growth-center.readiness') }}"
                    class="block font-bold uppercase tracking-wide text-slate-800"
                    @click="mobileMenuOpen = false"
                >
                    {{ __('SEO readiness') }}
                </a>
            @endif
        @elseif (Route::has('login'))
            <a
                href="{{ route('login') }}"
                class="block font-bold uppercase tracking-wide text-slate-800"
                @click="mobileMenuOpen = false"
            >
                {{ __('Log in') }}
            </a>
        @endauth
        <a
            href="{{ url('/#contact') }}"
            class="mt-3 block rounded bg-[#83b735] px-4 py-2 text-center text-xs font-bold text-white shadow-sm"
            @click="mobileMenuOpen = false"
        >
            {{ __('Book Callback') }}
        </a>
    </div>
</header>
