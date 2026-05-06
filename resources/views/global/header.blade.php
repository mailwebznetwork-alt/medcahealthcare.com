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
@endphp

<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur-md">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 md:px-6 lg:px-8">
        
        {{-- Brand & Logo --}}
        <a href="{{ url('/') }}" class="flex items-center gap-2.5">
            @if($logoSrc !== '')
                <img
                    src="{{ $logoSrc }}"
                    alt="Medca logo"
                    class="h-10 w-10 rounded-xl border border-slate-200 object-cover shadow-md shadow-clinical-600/15"
                />
            @else
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-clinical-600 to-clinical-800 text-white shadow-md shadow-clinical-600/25">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </span>
            @endif

            <div class="leading-tight">
                <span class="block text-sm font-semibold tracking-tight text-clinical-900 md:text-base">
                    {{ config('medca.brand_name', 'Medca Health Care') }}
                </span>
                <span class="hidden text-[11px] font-medium uppercase tracking-widest text-slate-500 sm:block">
                    Strategic Commander
                </span>
            </div>
        </a>

        {{-- Desktop Navigation --}}
        <div class="hidden items-center gap-4 md:flex">
            <nav class="flex items-center gap-5">
                @foreach($navItems as $item)
                    <a
                        href="{{ $item['href'] }}"
                        class="group relative rounded-xl border border-transparent px-3 py-2 text-[11px] font-semibold uppercase tracking-widest text-slate-600 transition hover:border-surgical-silver/40 hover:bg-medical-navy hover:text-surgical-silver"
                    >
                        <span>{{ $item['label'] }}</span>
                        <span class="absolute -bottom-1 left-2 h-px w-0 bg-surgical-silver shadow-[0_0_8px_rgba(226,232,240,0.9)] transition-all duration-200 group-hover:w-[calc(100%-1rem)]"></span>
                    </a>
                @endforeach
            </nav>

            @if($isSuperAdmin && Route::has('admin.site-architect.live-edit.toggle'))
                <form method="POST" action="{{ route('admin.site-architect.live-edit.toggle') }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-[#E2E8F0]/60 bg-[#0A1128] px-3 py-2 text-[11px] font-semibold uppercase tracking-widest text-[#E2E8F0] shadow-md">
                        {{ session('live_edit_enabled') ? 'Disable Live Edit' : 'Live Edit' }}
                    </button>
                </form>
            @endif
        </div>

        {{-- Mobile Navigation Drawer --}}
        <div x-data="{ open: false }" class="md:hidden">
            
            {{-- Hamburger Button --}}
            <button
                type="button"
                x-on:click="open = true"
                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white p-3 text-slate-700 shadow-sm transition hover:bg-slate-50"
                aria-label="Open navigation"
                :aria-expanded="open"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Teleported Drawer --}}
            <template x-teleport="body">
                <div
                    x-show="open"
                    x-cloak
                    x-transition.opacity
                    class="fixed inset-0 z-[99990] md:hidden"
                    style="pointer-events: auto;"
                    x-on:keydown.escape.window="open = false"
                >
                    {{-- Backdrop --}}
                    <div
                        class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                        x-on:click="open = false"
                        aria-hidden="true"
                    ></div>

                    {{-- Sidebar Panel --}}
                    <aside
                        class="absolute inset-y-0 right-0 flex h-full w-[85%] max-w-sm flex-col border-l border-slate-200 bg-white shadow-2xl transition-transform duration-300 ease-in-out transform"
                        :class="open ? 'translate-x-0' : 'translate-x-full'"
                        x-on:click.stop
                    >
                        {{-- Drawer Header --}}
                        <div class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-5">
                            <div class="flex flex-1 items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-clinical-100 bg-clinical-50 text-clinical-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold tracking-wide text-clinical-900">Medca Navigation</p>
                                    @if($isSuperAdmin)
                                        <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Strategic Commander</p>
                                    @endif
                                </div>
                            </div>
                            <button
                                type="button"
                                x-on:click="open = false"
                                class="rounded-xl border border-slate-200 bg-slate-50 p-2 text-slate-700 transition hover:bg-slate-100"
                                aria-label="Close navigation"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Drawer Navigation Links --}}
                        <nav class="custom-scrollbar flex-1 overflow-y-auto bg-white px-5 py-4">
                            @foreach($navItems as $item)
                                <a
                                    href="{{ $item['href'] }}"
                                    x-on:click="open = false"
                                    class="flex min-h-[60px] items-center border-b border-slate-100 px-1 text-sm font-semibold uppercase tracking-wide text-slate-800 transition hover:bg-slate-50"
                                >
                                    {{ $item['label'] }}
                                </a>
                            @endforeach

                            {{-- Super Admin Actions --}}
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

                        {{-- Drawer Footer Actions --}}
                        <div class="border-t border-slate-200 bg-slate-50 p-4">
                            <div class="grid grid-cols-2 gap-2">
                                <a
                                    href="tel:+918884999002"
                                    class="flex min-h-[52px] items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-clinical-800 shadow-sm transition hover:bg-slate-50"
                                >
                                    Call Now
                                </a>
                                <a
                                    href="https://wa.me/918884999002"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex min-h-[52px] items-center justify-center rounded-xl border border-emerald-700/40 bg-emerald-700 px-3 text-sm font-bold text-white transition hover:bg-emerald-800"
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
</header>