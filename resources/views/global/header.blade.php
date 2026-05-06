@php
    $logoSrc = asset('images/medca-logo.png');
    $isSuperAdmin = auth()->check() && strtolower((string) auth()->user()?->role) === 'super_admin';

    /** @var array<int, array{label: string, href: string}>|null $publicNavHeader */
    $navItems = $publicNavHeader ?? [
        ['label' => __('Home'), 'href' => url('/')],
        ['label' => __('About Us'), 'href' => url('/#about')],
        ['label' => __('Services'), 'href' => url('/#services')],
        ['label' => __('Locations'), 'href' => url('/#locations')],
        ['label' => __('Careers'), 'href' => route('careers.index')],
        ['label' => __('Contact Us'), 'href' => url('/#contact')],
    ];

    $medcaGmbUrl = trim((string) config('medca.public_profile_url', ''));
    $medcaGmbValid = $medcaGmbUrl !== '' && filter_var($medcaGmbUrl, FILTER_VALIDATE_URL);

    $navLinkBase = 'inline-flex items-center py-2 text-xs font-medium uppercase tracking-[0.06em] transition-colors duration-200 md:text-sm focus-visible:outline-none';
    $navLinkDefault = 'text-[#0046ad] hover:text-[#001e5c] focus-visible:text-[#001e5c]';
    $navLinkActive = 'text-[#581c87] hover:text-[#3b0764] focus-visible:text-[#3b0764]';
    $navDrawerTriggerClass = 'inline-flex items-center justify-center rounded-lg border border-clinical-200 bg-white p-2 text-[#0046ad] shadow-sm transition-colors duration-200 hover:border-clinical-300 hover:bg-clinical-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-clinical-500/40';
@endphp

{{-- Sticky stack: slim topbar (~32–36px) + navbar row (~78–90px min). Approximate total px: config('medca.marketing_sticky_header_approx_px'). --}}
<header class="sticky top-0 z-40 w-full font-sans">
    <div class="w-full border-b border-[#001433] bg-[#001f5c]">
        <div class="mx-auto flex h-9 min-h-[32px] items-center justify-between gap-3 px-4 md:px-6 lg:px-8">
            <p class="min-w-0 flex-1 truncate text-left text-[11px] font-medium leading-none tracking-wide text-white md:text-xs">{{ config('medca.top_bar_claim') }}</p>
            <div class="flex shrink-0 items-center justify-end">
                @if ($medcaGmbValid)
                    <a
                        href="{{ $medcaGmbUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex max-w-[min(52vw,14rem)] items-center gap-1.5 truncate text-[11px] font-medium leading-none text-white underline decoration-white/50 underline-offset-2 transition-colors duration-200 hover:text-white hover:decoration-white md:max-w-none md:text-xs"
                        aria-label="{{ __('Google Business Profile') }} — {{ config('medca.location_display') }}"
                    >
                        <svg class="h-3.5 w-3.5 shrink-0 text-white md:h-4 md:w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        <span class="min-w-0 truncate">{{ config('medca.location_display') }}</span>
                    </a>
                @else
                    <span class="inline-flex max-w-[min(52vw,14rem)] items-center gap-1.5 text-[11px] font-medium leading-none tracking-wide text-white/95 md:max-w-none md:text-xs">
                        <svg class="h-3.5 w-3.5 shrink-0 text-white md:h-4 md:w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        <span class="min-w-0 truncate">{{ config('medca.location_display') }}</span>
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="w-full border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto flex min-h-[78px] max-w-6xl items-center justify-between gap-4 px-4 md:min-h-[84px] md:gap-6 md:px-6 lg:min-h-[86px] lg:gap-8 lg:px-8">
            {{-- Brand & Logo --}}
            <a href="{{ url('/') }}" class="inline-flex min-w-0 max-w-[min(100%,78vw)] items-center gap-2 text-sm md:max-w-none md:gap-2.5 md:text-base text-[#0046ad] focus:outline-none focus-visible:ring-2 focus-visible:ring-clinical-500/30" aria-label="{{ config('medca.brand_name') }} — {{ config('medca.tagline') }} — {{ __('Home') }}">
                @if($logoSrc !== '')
                    <img
                        src="{{ $logoSrc }}"
                        alt="{{ config('medca.brand_name') }}"
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
                    <span class="min-w-0 truncate text-[170%] font-semibold leading-tight tracking-tight text-slate-900">{{ config('medca.brand_name', 'Medca Health Care') }}</span>
                    <span class="mt-0.5 min-w-0 truncate text-[0.625rem] font-bold uppercase leading-none tracking-[0.18em] text-[#0046ad] md:text-[0.6875rem]">{{ config('medca.tagline') }}</span>
                </div>
            </a>

            {{-- Desktop Navigation --}}
            <div class="hidden min-w-0 flex-1 items-center justify-end md:flex">
                <nav class="flex min-w-0 flex-1 items-center justify-end" aria-label="{{ __('Primary') }}">
                    <ul class="flex flex-wrap items-center justify-end gap-x-4 md:gap-x-6 lg:gap-x-8">
                        @foreach($navItems as $item)
                            @php($isNavCurrent = \App\Support\PublicNav::isCurrent($item['href']))
                            <li class="flex items-center">
                                <a
                                    href="{{ $item['href'] }}"
                                    @if ($isNavCurrent) aria-current="page" @endif
                                    class="{{ $navLinkBase }} {{ $isNavCurrent ? $navLinkActive : $navLinkDefault }}"
                                >
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                @if($isSuperAdmin && Route::has('admin.site-architect.live-edit.toggle'))
                    <form method="POST" action="{{ route('admin.site-architect.live-edit.toggle') }}" class="ml-4 shrink-0 md:ml-6">
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
                        class="fixed inset-0 z-[99990] md:hidden"
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
                                        <p class="text-sm font-semibold tracking-wide text-clinical-900">{{ __('Medca Navigation') }}</p>
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
                                    @php($drawerCurrent = \App\Support\PublicNav::isCurrent($item['href']))
                                    <a
                                        href="{{ $item['href'] }}"
                                        x-on:click="open = false"
                                        @if ($drawerCurrent) aria-current="page" @endif
                                        class="flex min-h-[60px] items-center border-b border-slate-100 px-1 text-sm font-medium uppercase tracking-[0.05em] transition-colors duration-200 hover:bg-slate-50 focus-visible:outline-none {{ $drawerCurrent ? $navLinkActive : 'text-[#0046ad] hover:text-[#001e5c]' }}"
                                    >
                                        {{ $item['label'] }}
                                    </a>
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

                            <div class="border-t border-slate-200 bg-slate-50 p-4">
                                <div class="grid grid-cols-2 gap-2">
                                    <a
                                        href="tel:+918884999002"
                                        class="flex min-h-[52px] items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-clinical-800 shadow-sm transition-colors duration-200 hover:bg-slate-50"
                                    >
                                        Call Now
                                    </a>
                                    <a
                                        href="https://wa.me/918884999002"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex min-h-[52px] items-center justify-center rounded-xl border border-emerald-700/40 bg-emerald-700 px-3 text-sm font-bold text-white transition-colors duration-200 hover:bg-emerald-800"
                                    >
                                        WhatsApp
                                    </a>
                                </div>
                            </div>
                        </aside>
                    </div>
                </template>
            </div>
        </div>
    </div>
</header>
