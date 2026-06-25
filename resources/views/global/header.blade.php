@php
    $themeResolver = app(\App\Services\Theme\ThemeResolver::class);
    $themeBranding = $themeResolver->branding();
    $themeRepo = app(\App\Services\Theme\ThemeConfigRepository::class);
    $logoSrc = $themeRepo->assetUrl($themeBranding['logo_path'] ?? null) ?: asset('images/medca-logo.png');
    $brandName = $themeBranding['brand_name'] ?? config('medca.brand_name');
    $brandTagline = $themeBranding['tagline'] ?? config('medca.tagline');
    $isSuperAdmin = auth()->check() && strtolower((string) auth()->user()?->role) === 'super_admin';
    $navItems = app(\App\Services\SiteNavigationResolver::class)->headerNav();
    $medcaCallHref = \App\Support\BlockContent::telHref();
    $medcaWhatsAppUrl = (string) ($whatsAppPrimaryUrl ?? \App\Support\BlockContent::whatsAppUrl());
    $headerPresetClass = $themeResolver->headerPresetClass();
    $headerConfig = $themeResolver->headerConfiguration();
    $layoutShellClass = $themeResolver->layoutShellClass();

    $navLinkBase = 'medca-primary-nav-link inline-flex items-center whitespace-nowrap py-2 font-semibold uppercase transition-colors duration-200 focus-visible:outline-none';
    $navLinkDefault = 'text-medca-primary hover:text-medca-primary-hover focus-visible:text-medca-primary-hover';
    $navLinkActive = 'text-[#581c87] hover:text-[#3b0764] focus-visible:text-[#3b0764]';
    $navDrawerTriggerClass = 'inline-flex items-center justify-center rounded-lg border border-clinical-200 bg-white p-2 text-medca-primary shadow-sm transition-colors duration-200 hover:border-clinical-300 hover:bg-clinical-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-clinical-500/40';
@endphp

{{-- Desktop nav — inline so critical layout applies without waiting on Vite rebuild --}}
<style>
    @media (min-width: 768px) {
        body.medca-public-surface .medca-site-header .medca-primary-nav-link {
            font-size: 15px !important;
            line-height: 1.3125rem !important;
        }
    }

    body.medca-public-surface .medca-nav-dropdown-anchor {
        position: relative;
    }

    body.medca-public-surface .medca-site-header .medca-nav-dropdown {
        left: auto;
        right: 0;
        transform: translateY(0.25rem);
    }

    body.medca-public-surface .medca-site-header .medca-nav-dropdown .medca-nav-flyout {
        left: 100%;
        right: auto;
    }

    body.medca-public-surface .medca-nav-dropdown-anchor:hover > .medca-nav-dropdown,
    body.medca-public-surface .medca-nav-dropdown-anchor:focus-within > .medca-nav-dropdown,
    body.medca-public-surface .medca-nav-flyout-parent:hover > .medca-nav-flyout,
    body.medca-public-surface .medca-nav-flyout-parent:focus-within > .medca-nav-flyout {
        clip: auto;
        clip-path: none;
    }

    body.medca-public-surface .medca-nav-dropdown-anchor:hover > .medca-nav-dropdown,
    body.medca-public-surface .medca-nav-dropdown-anchor:focus-within > .medca-nav-dropdown {
        transform: translateY(0);
    }
</style>

{{-- Sticky stack: slim topbar (~32–36px) + navbar row (~78–90px min). Approximate total px: config('medca.marketing_sticky_header_approx_px'). --}}
<header class="medca-site-header sticky top-0 z-40 w-full font-sans {{ $headerPresetClass }}" data-header-preset="{{ $themeResolver->headerPreset() }}">
    @if ($headerConfig['show_top_bar'] ?? true)
    <div class="w-full border-b border-medca-navy-border bg-medca-navy medca-header-top-bar">
        <div @class(['mx-auto flex h-10 min-h-[36px] items-center justify-start px-4 md:h-9 md:min-h-[32px] md:px-6 lg:px-8', $layoutShellClass])>
            <p class="min-w-0 truncate text-left text-sm font-semibold leading-snug tracking-wide text-white md:text-base">
                {{ config('medca.top_bar_claim') }}
            </p>
        </div>
    </div>
    @endif

    <div class="w-full border-b border-slate-200 bg-white shadow-sm">
        <div @class(['mx-auto flex min-h-[78px] items-center justify-between gap-4 px-4 md:min-h-[84px] md:gap-6 md:px-6 lg:min-h-[86px] lg:gap-8 lg:px-8', $layoutShellClass])>
            {{-- Brand & Logo --}}
            <a href="{{ url('/') }}" class="inline-flex min-w-0 max-w-[min(100%,78vw)] items-center gap-2 text-sm md:max-w-none md:gap-2.5 md:text-base text-medca-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-clinical-500/30" aria-label="{{ $brandName }} — {{ $brandTagline }} — {{ __('Home') }}">
                @if($logoSrc !== '')
                    <img
                        src="{{ $logoSrc }}"
                        alt="{{ $brandName }}"
                        class="h-7 w-auto shrink-0 object-contain md:h-8"
                    />
                @else
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-clinical-600 to-clinical-800 text-white shadow-md shadow-clinical-600/25">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </span>
                @endif

                <div class="flex min-w-0 flex-col justify-center border-l border-slate-200 pl-2 sm:pl-2.5">
                    <span class="min-w-0 truncate text-[170%] font-semibold leading-tight tracking-tight text-medca-primary">{{ config('medca.brand_name', 'LetsSee') }}</span>
                    <span class="mt-0.5 min-w-0 truncate text-[0.625rem] font-bold uppercase leading-none tracking-[0.18em] text-medca-primary md:text-[0.6875rem]">{{ config('medca.tagline') }}</span>
                </div>
            </a>

            {{-- Desktop Navigation --}}
            <div class="hidden shrink-0 items-center justify-end gap-1 overflow-visible md:flex">
                <nav class="medca-primary-nav flex shrink-0 items-center justify-end overflow-visible" aria-label="{{ __('Primary') }}">
                    <ul class="flex flex-nowrap items-center justify-end overflow-visible">
                        @foreach($navItems as $item)
                            <x-public.nav-item
                                :item="$item"
                                :nav-link-base="$navLinkBase"
                                :nav-link-default="$navLinkDefault"
                                :nav-link-active="$navLinkActive"
                                :show-border="! $loop->first"
                            />
                        @endforeach
                    </ul>
                </nav>

                @if ($themeResolver->headerConfigEnabled('show_search') && Route::has('public.services.index'))
                    <a href="{{ route('public.services.index') }}" class="{{ $navLinkBase }} {{ $navLinkDefault }} ml-2 shrink-0">
                        {{ __('Search') }}
                    </a>
                @endif

                @if ($themeResolver->headerConfigEnabled('show_secondary_menu'))
                    <div class="ml-2 hidden shrink-0 items-center gap-2 lg:flex">
                        <a href="{{ $medcaCallHref }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-medca-primary hover:bg-slate-50">{{ __('Call Us') }}</a>
                        <a href="{{ $medcaWhatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-500">{{ __('WhatsApp Us') }}</a>
                    </div>
                @endif

                @if($isSuperAdmin && Route::has('admin.site-architect.live-edit.toggle'))
                    <form method="POST" action="{{ route('admin.site-architect.live-edit.toggle') }}" class="ml-2 shrink-0">
                        @csrf
                        <button type="submit" class="rounded-xl border border-[#E2E8F0]/60 bg-[#0A1128] px-3 py-2 text-[11px] font-semibold uppercase tracking-widest text-[#E2E8F0] shadow-md">
                            {{ session('live_edit_enabled') ? 'Disable Live Edit' : 'Live Edit' }}
                        </button>
                    </form>
                @endif
            </div>

            {{-- Mobile Navigation Drawer --}}
            <div x-data="{ open: false }" class="md:hidden">
                <button
                    type="button"
                    x-on:click="open = true"
                    class="{{ $navDrawerTriggerClass }}"
                    aria-label="{{ __('Open navigation') }}"
                    :aria-expanded="open"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <template x-teleport="body">
                    <div
                        x-show="open"
                        x-cloak
                        x-transition.opacity
                        class="medca-site-header-drawer fixed inset-0 z-[99990] md:hidden"
                        style="pointer-events: auto;"
                        x-on:keydown.escape.window="open = false"
                    >
                        <div
                            class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                            x-on:click="open = false"
                            aria-hidden="true"
                        ></div>

                        <aside
                            class="absolute inset-y-0 right-0 flex h-full w-[85%] max-w-sm flex-col border-l border-slate-200 bg-white shadow-2xl transition-transform duration-300 ease-in-out transform"
                            :class="open ? 'translate-x-0' : 'translate-x-full'"
                            x-on:click.stop
                        >
                            <div class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-5">
                                <div class="flex flex-1 items-center gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-clinical-100 bg-clinical-50 text-clinical-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold tracking-wide text-clinical-900">{{ __('LetsSee Navigation') }}</p>
                                        @if($isSuperAdmin)
                                            <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">{{ __('Strategic Commander') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    x-on:click="open = false"
                                    class="rounded-xl border border-slate-200 bg-slate-50 p-2 text-slate-700 transition hover:bg-slate-100"
                                    aria-label="{{ __('Close navigation') }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <nav class="custom-scrollbar flex-1 overflow-y-auto bg-white px-5 py-4">
                                @foreach($navItems as $item)
                                    <x-public.nav-item
                                        :item="$item"
                                        :nav-link-base="$navLinkBase"
                                        :nav-link-default="$navLinkDefault"
                                        :nav-link-active="$navLinkActive"
                                        :is-mobile="true"
                                    />
                                @endforeach

                                @if($isSuperAdmin)
                                    <div class="mt-5 space-y-3">
                                        @if(Route::has('admin.site-architect.live-edit.toggle'))
                                            <form method="POST" action="{{ route('admin.site-architect.live-edit.toggle') }}">
                                                @csrf
                                                <button type="submit" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-slate-800 shadow-sm transition hover:bg-slate-50">
                                                    {{ session('live_edit_enabled') ? 'Disable Edit Mode' : 'Enable Edit Mode' }}
                                                </button>
                                            </form>
                                        @endif

                                        @if(Route::has('admin.site-architect.drafts.publish'))
                                            <form method="POST" action="{{ route('admin.site-architect.drafts.publish') }}">
                                                @csrf
                                                <button type="submit" class="block w-full rounded-2xl border border-clinical-600 bg-clinical-600 px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-white shadow-md transition hover:bg-clinical-700">
                                                    Publish Changes
                                                </button>
                                            </form>
                                        @endif

                                        @if(Route::has('admin.dashboard'))
                                            <a
                                                href="{{ route('admin.dashboard') }}"
                                                x-on:click="open = false"
                                                class="block w-full rounded-2xl border border-clinical-200 bg-clinical-50 px-4 py-3 text-left text-sm font-semibold uppercase tracking-widest text-clinical-800 transition hover:bg-clinical-100"
                                            >
                                                Super Admin Console
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </nav>

                            @if ($themeResolver->headerConfigEnabled('mobile_cta_enabled') || $themeResolver->headerConfigEnabled('mobile_whatsapp_enabled'))
                            <div class="border-t border-slate-200 bg-slate-50 p-4">
                                <div @class(['grid gap-2', 'grid-cols-2' => $themeResolver->headerConfigEnabled('mobile_cta_enabled') && $themeResolver->headerConfigEnabled('mobile_whatsapp_enabled'), 'grid-cols-1' => ! ($themeResolver->headerConfigEnabled('mobile_cta_enabled') && $themeResolver->headerConfigEnabled('mobile_whatsapp_enabled'))])>
                                    @if ($themeResolver->headerConfigEnabled('mobile_cta_enabled'))
                                    <a
                                        href="{{ $medcaCallHref }}"
                                        class="flex min-h-[52px] items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-clinical-800 shadow-sm transition-colors duration-200 hover:bg-slate-50"
                                    >
                                        {{ __('Call Us') }}
                                    </a>
                                    @endif
                                    @if ($themeResolver->headerConfigEnabled('mobile_whatsapp_enabled'))
                                    <a
                                        href="{{ $medcaWhatsAppUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex min-h-[52px] items-center justify-center rounded-xl bg-emerald-600 px-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-500"
                                    >
                                        {{ __('WhatsApp Us') }}
                                    </a>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </aside>
                    </div>
                </template>
            </div>
        </div>
    </div>
</header>
