@props([
    'pageTitle' => null,
    'welcomeLine' => null,
])

@php
    $user = Auth::user();
    $parts = preg_split('/\s+/', trim($user->name ?? 'User'));
    $initials = mb_strtoupper(
        collect($parts)
            ->filter()
            ->take(2)
            ->map(fn ($p) => mb_substr($p, 0, 1))
            ->implode('')
    );

    $resolvedTitle = $pageTitle;
    if ($resolvedTitle === null && isset($header)) {
        $resolvedTitle = trim(strip_tags($header->toHtml()));
    }
    $resolvedTitle ??= __('Dashboard Overview');

    $resolvedWelcome = $welcomeLine;
    if ($resolvedWelcome === null) {
        $resolvedWelcome = match (true) {
            request()->routeIs('profile.*') => __('Account security and identity preferences.'),
            request()->routeIs('settings.*') => __('Workspace configuration and preferences.'),
            request()->routeIs('operations.job-portal.*', 'operations.pin-codes.*', 'operations.services.*', 'modules.operations') => __('Run-state, hiring, coverage, and operational management workspace.'),
            request()->routeIs('site-architect.*') => __('Structure-only content, reusable blocks, country/state GEO.'),
            request()->routeIs('user-management.*') => __('People, access, and directory control.'),
            request()->routeIs('modules.marketing', 'marketing.dashboard', 'marketing.*') => __('Unified tracking, performance, and decision signals.'),
            request()->routeIs('growth-center.*') => __('Competitor intelligence, SEO, coverage, and analytics.'),
            request()->routeIs('modules.*') => __('Focused module workspace.'),
            default => __('Welcome back — here is your intelligence snapshot.'),
        };
    }

    $showAdminNotifications = in_array(strtolower((string) ($user->role ?? '')), ['admin', 'super_admin'], true);

@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'MarkOnMinds') }} — {{ $resolvedTitle }}</title>
        @stack('head')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=noto-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/admin/admin.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body
        class="mom-body font-sans antialiased text-[var(--text-primary)]"
        x-data="{ mobileNav: false }"
        x-effect="document.documentElement.classList.toggle('mom-mobile-nav-open', mobileNav)"
        @keydown.escape.window="mobileNav = false"
        @close-mobile-nav.window="mobileNav = false"
    >
        <div class="mom-noise" aria-hidden="true"></div>

        <div id="mom-shell" class="mom-shell flex h-[100dvh] max-h-[100dvh] min-h-0 w-full overflow-hidden">
            {{-- Sidebar — fixed drawer on mobile, flex column on desktop --}}
            <aside
                class="mom-sidebar-pane mom-mobile-drawer fixed inset-y-0 left-0 z-[60] flex min-h-0 w-[80vw] max-w-[280px] -translate-x-full flex-col border-r border-[var(--border-panel-soft)] transition-transform duration-320 ease-premium lg:relative lg:inset-auto lg:z-auto lg:h-full lg:w-[260px] lg:max-w-none lg:shrink-0 lg:translate-x-0"
                :class="{ '!translate-x-0': mobileNav }"
                @keydown.escape.window="mobileNav = false"
            >
                <div class="flex h-[72px] shrink-0 items-center justify-between gap-3 border-b border-[var(--border-panel-soft)] px-4 lg:px-6">
                    <span class="text-[calc(1.125rem*1.3)] font-semibold leading-tight tracking-tight text-mom-wordmark">{{ config('app.name', 'MarkOnMinds') }}</span>
                    <button
                        type="button"
                        class="mom-mobile-menu-toggle flex h-11 w-11 items-center justify-center rounded-full border border-[var(--border-panel-soft)] text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:border-[rgba(197,160,89,0.2)] hover:text-[var(--text-primary)] lg:hidden"
                        @click="mobileNav = false"
                        aria-label="{{ __('Close navigation') }}"
                    >
                        <i data-lucide="x" class="h-[18px] w-[18px]"></i>
                    </button>
                </div>

                <x-mom-sidebar-nav :user="$user" />
            </aside>

            {{-- Mobile overlay — inside shell, below drawer, above content --}}
            <div
                x-show="mobileNav"
                x-transition.opacity.duration.320ms
                class="mom-mobile-drawer-overlay pointer-events-auto fixed inset-0 z-[50] lg:hidden"
                style="display: none;"
                @click="mobileNav = false"
                aria-hidden="true"
            ></div>

            {{-- Main column: neutral app canvas; warm text scope optional --}}
            <div class="mom-content-pane mom-main-matte-brown-fg flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
                {{-- Topbar --}}
                <header
                    class="mom-topbar-scrim mom-sticky-topbar flex min-h-[72px] shrink-0 items-center gap-3 border-b border-[var(--border-panel-soft)] px-4 shadow-mom-surface sm:gap-6 sm:px-6 lg:px-8"
                >
                    <button
                        type="button"
                        class="mom-mobile-menu-toggle flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-[var(--border-panel-soft)] text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:border-[rgba(197,160,89,0.2)] hover:text-[var(--text-primary)] lg:hidden"
                        @click="mobileNav = true"
                        aria-label="{{ __('Open navigation') }}"
                        :aria-expanded="mobileNav"
                    >
                        <i data-lucide="panel-left" class="h-[18px] w-[18px]"></i>
                    </button>

                    <div class="min-w-0 flex-1 lg:hidden">
                        <h1 class="mom-mobile-topbar-title mom-title-page truncate">{{ $resolvedTitle }}</h1>
                        <p class="mom-subtext mt-0.5 truncate text-xs">{{ $resolvedWelcome }}</p>
                    </div>

                    <div class="hidden min-w-0 flex-1 md:block">
                        <h1 class="mom-title-page truncate">{{ $resolvedTitle }}</h1>
                        <p class="mom-subtext mt-1 truncate">{{ $resolvedWelcome }}</p>
                    </div>

                    <div class="mx-auto hidden max-w-md flex-1 px-4 md:flex">
                        <form
                            method="get"
                            action="{{ route('workspace.search') }}"
                            role="search"
                            aria-label="{{ __('Search workspace') }}"
                            class="relative flex w-full items-center"
                        >
                            <label class="relative flex w-full items-center">
                                <span class="pointer-events-none absolute left-4 text-[var(--text-muted)]">
                                    <i data-lucide="search" class="h-[18px] w-[18px]"></i>
                                </span>
                                <input
                                    id="workspace-global-search-q"
                                    name="q"
                                    type="search"
                                    value="{{ request()->routeIs('workspace.search') ? (string) request('q', '') : '' }}"
                                    minlength="2"
                                    maxlength="120"
                                    placeholder="{{ __('Search pages, leads, services…') }}"
                                    autocomplete="off"
                                    class="w-full rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[color:rgba(28,22,22,0.92)] py-2.5 pl-11 pr-24 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-muted)] shadow-mom-inner outline-none ring-offset-0 transition-all duration-320 ease-premium focus:border-[rgba(197,160,89,0.35)] focus:shadow-[0_0_24px_rgba(197,160,89,0.12)]"
                                />
                                <kbd
                                    class="pointer-events-none absolute right-3 hidden rounded-md border border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.03)] px-2 py-0.5 font-mono text-[11px] text-[var(--text-muted)] sm:inline-block"
                                >⌘ K</kbd>
                            </label>
                        </form>
                    </div>

                    @if ($showAdminNotifications)
                        <livewire:admin.notification-bell />
                    @endif

                    <div
                        class="relative shrink-0"
                        x-data="{ profileOpen: false }"
                        @keydown.escape.window="profileOpen = false"
                    >
                        <button
                            type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-[rgba(197,160,89,0.28)] bg-[rgba(197,160,89,0.08)] text-xs font-semibold text-mom-gold transition-all duration-320 ease-premium hover:border-[rgba(197,160,89,0.4)] hover:shadow-[0_0_20px_rgba(197,160,89,0.12)]"
                            @click="profileOpen = ! profileOpen"
                            x-bind:aria-expanded="profileOpen"
                            aria-haspopup="true"
                            aria-controls="mom-profile-menu"
                            aria-label="{{ __('Account menu') }}"
                        >
                            <span aria-hidden="true">{{ $initials ?: 'AU' }}</span>
                        </button>

                        <div
                            id="mom-profile-menu"
                            x-show="profileOpen"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-1"
                            @click.outside="profileOpen = false"
                            class="absolute right-0 top-[calc(100%+0.5rem)] z-50 w-[min(18rem,calc(100vw-2.5rem))] rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] p-4 shadow-mom-elevated ring-1 ring-[rgba(197,160,89,0.08)]"
                            style="display: none;"
                            role="menu"
                            aria-orientation="vertical"
                        >
                            <div class="mom-backend-hairline-b flex items-start gap-3 pb-4">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-[rgba(197,160,89,0.28)] bg-[rgba(197,160,89,0.08)] text-xs font-semibold tracking-wide text-mom-gold"
                                    aria-hidden="true"
                                >
                                    {{ $initials ?: 'AU' }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-[var(--text-primary)]">{{ $user->name }}</p>
                                    <p class="truncate text-[13px] text-[var(--text-secondary)]">{{ $user->email }}</p>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2">
                                <a
                                    href="{{ route('profile.edit') }}"
                                    role="menuitem"
                                    class="mom-subtext inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[var(--text-muted)] transition-colors duration-320 ease-premium hover:text-[var(--text-secondary)]"
                                    @click="profileOpen = false"
                                >
                                    <i data-lucide="circle-user" class="h-3.5 w-3.5 shrink-0"></i>
                                    {{ __('Account') }}
                                </a>
                                <form method="POST" action="{{ route('logout') }}" class="inline">
                                    @csrf
                                    <button
                                        type="submit"
                                        role="menuitem"
                                        class="mom-subtext inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[var(--text-muted)] transition-colors duration-320 ease-premium hover:text-[var(--danger)]"
                                    >
                                        <i data-lucide="log-out" class="h-3.5 w-3.5 shrink-0"></i>
                                        {{ __('Log out') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="mom-backend-main mom-scroll-main min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('schema-forms')
        @stack('scripts')
        @livewireScripts
        <script>
            document.addEventListener('keydown', function (e) {
                if (!(e.metaKey || e.ctrlKey) || e.key.toLowerCase() !== 'k') {
                    return;
                }
                var input = document.getElementById('workspace-global-search-q');
                if (!input || input.offsetParent === null) {
                    return;
                }
                e.preventDefault();
                input.focus();
                input.select?.();
            });
        </script>
    </body>
</html>
