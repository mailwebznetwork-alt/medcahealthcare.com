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
        $resolvedWelcome = request()->routeIs('profile.*')
            ? __('Account security and identity preferences.')
            : __('Welcome back — here is your intelligence snapshot.');
    }

    $navDashboard = request()->routeIs('dashboard');
    $navProfile = request()->routeIs('profile.edit');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'MarkOnMinds') }} — {{ $resolvedTitle }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=noto-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/shell.js'])
    </head>
    <body
        class="mom-body font-sans antialiased text-[var(--text-primary)]"
        x-data="{ mobileNav: false }"
        @keydown.escape.window="mobileNav = false"
    >
        <div class="mom-noise" aria-hidden="true"></div>

        {{-- Mobile overlay --}}
        <div
            x-show="mobileNav"
            x-transition.opacity.duration.320ms
            class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
            style="display: none;"
            @click="mobileNav = false"
        ></div>

        <div id="mom-shell" class="relative z-10 flex min-h-screen">
            {{-- Sidebar --}}
            <aside
                class="mom-main-pane fixed inset-y-0 left-0 z-50 flex w-[260px] -translate-x-full flex-col border-r border-[rgba(255,255,255,0.045)] transition-transform duration-320 ease-premium lg:static lg:translate-x-0"
                :class="{ '!translate-x-0': mobileNav }"
            >
                <div class="flex h-[72px] shrink-0 items-center gap-3 border-b border-[rgba(255,255,255,0.045)] px-6">
                    <span class="text-lg font-semibold tracking-tight text-mom-gold">MarkOnMinds</span>
                </div>

                <nav class="custom-scrollbar flex flex-1 flex-col gap-8 overflow-y-auto px-4 py-8">
                    <div>
                        <p class="mom-micro mb-3 px-3">Main</p>
                        <ul class="space-y-1">
                            <li>
                                <a
                                    href="{{ route('dashboard') }}"
                                    @class([
                                        'mom-nav-active flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-mom-gold transition-all duration-320 ease-premium' => $navDashboard,
                                        'flex items-center gap-3 rounded-full px-3 py-2.5 text-sm font-medium text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)] hover:shadow-[0_0_22px_rgba(212,169,95,0.06)]' => ! $navDashboard,
                                    ])
                                >
                                    <i data-lucide="layout-dashboard" class="h-[18px] w-[18px] shrink-0 {{ $navDashboard ? '' : 'opacity-80' }}"></i>
                                    <span>{{ __('Dashboard') }}</span>
                                </a>
                            </li>
                            <li>
                                <a
                                    href="{{ route('profile.edit') }}"
                                    @class([
                                        'mom-nav-active flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-mom-gold transition-all duration-320 ease-premium' => $navProfile,
                                        'flex items-center gap-3 rounded-full px-3 py-2.5 text-sm font-medium text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)] hover:shadow-[0_0_22px_rgba(212,169,95,0.06)]' => ! $navProfile,
                                    ])
                                >
                                    <i data-lucide="circle-user" class="h-[18px] w-[18px] shrink-0 {{ $navProfile ? '' : 'opacity-80' }}"></i>
                                    <span>{{ __('Profile') }}</span>
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="flex items-center gap-3 rounded-full px-3 py-2.5 text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)] hover:shadow-[0_0_22px_rgba(212,169,95,0.06)]"
                                >
                                    <i data-lucide="line-chart" class="h-[18px] w-[18px] shrink-0 opacity-80"></i>
                                    <span class="text-sm font-medium">{{ __('Analytics') }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <p class="mom-micro mb-3 px-3">Operations</p>
                        <ul class="space-y-1">
                            <li>
                                <a
                                    href="#"
                                    class="flex items-center gap-3 rounded-full px-3 py-2.5 text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)] hover:shadow-[0_0_22px_rgba(212,169,95,0.06)]"
                                >
                                    <i data-lucide="users" class="h-[18px] w-[18px] shrink-0 opacity-80"></i>
                                    <span class="text-sm font-medium">Users</span>
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="flex items-center gap-3 rounded-full px-3 py-2.5 text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)] hover:shadow-[0_0_22px_rgba(212,169,95,0.06)]"
                                >
                                    <i data-lucide="folder-kanban" class="h-[18px] w-[18px] shrink-0 opacity-80"></i>
                                    <span class="text-sm font-medium">Projects</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <p class="mom-micro mb-3 px-3">Insights</p>
                        <ul class="space-y-1">
                            <li>
                                <a
                                    href="#"
                                    class="flex items-center gap-3 rounded-full px-3 py-2.5 text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)] hover:shadow-[0_0_22px_rgba(212,169,95,0.06)]"
                                >
                                    <i data-lucide="sparkles" class="h-[18px] w-[18px] shrink-0 opacity-80"></i>
                                    <span class="text-sm font-medium">AI Studio</span>
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="flex items-center gap-3 rounded-full px-3 py-2.5 text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)] hover:shadow-[0_0_22px_rgba(212,169,95,0.06)]"
                                >
                                    <i data-lucide="file-chart-column" class="h-[18px] w-[18px] shrink-0 opacity-80"></i>
                                    <span class="text-sm font-medium">Reports</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>

                <div class="border-t border-[rgba(255,255,255,0.045)] p-4">
                    <div
                        class="rounded-mom-lg border border-[rgba(255,255,255,0.045)] bg-[linear-gradient(180deg,rgba(255,255,255,0.03),rgba(255,255,255,0.01))] p-6 shadow-mom-inner"
                        style="box-shadow: var(--shadow-inner), 0 0 36px rgba(212,169,95,0.05);"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-[rgba(212,169,95,0.22)] bg-[rgba(212,169,95,0.08)] text-xs font-semibold tracking-wide text-mom-gold"
                                aria-hidden="true"
                            >
                                {{ $initials ?: 'AU' }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-[var(--text-primary)]">{{ $user->name }}</p>
                                <p class="truncate text-[13px] text-[var(--text-secondary)]">{{ $user->email }}</p>
                                <div class="mt-3 flex items-center gap-2">
                                    <a
                                        href="{{ route('profile.edit') }}"
                                        class="mom-subtext inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[var(--text-muted)] transition-colors duration-320 ease-premium hover:text-[var(--text-secondary)]"
                                    >
                                        <i data-lucide="settings" class="h-3.5 w-3.5"></i>
                                        Settings
                                    </a>
                                    <form method="POST" action="{{ route('logout') }}" class="inline">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="mom-subtext inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[var(--text-muted)] transition-colors duration-320 ease-premium hover:text-[var(--danger)]"
                                        >
                                            <i data-lucide="log-out" class="h-3.5 w-3.5"></i>
                                            Log out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Main column: same pane background as sidebar --}}
            <div class="mom-main-pane flex min-w-0 flex-1 flex-col lg:ml-0">
                {{-- Topbar --}}
                <header
                    class="sticky top-0 z-30 flex h-[72px] items-center gap-6 border-b border-[rgba(255,255,255,0.045)] bg-[rgba(7,7,7,0.72)] px-8 shadow-mom-surface backdrop-blur-xl backdrop-saturate-150"
                >
                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-[rgba(255,255,255,0.045)] text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)] lg:hidden"
                        @click="mobileNav = true"
                        aria-label="Open navigation"
                    >
                        <i data-lucide="panel-left" class="h-[18px] w-[18px]"></i>
                    </button>

                    <div class="hidden min-w-0 flex-1 md:block">
                        <h1 class="mom-title-page truncate">{{ $resolvedTitle }}</h1>
                        <p class="mom-subtext mt-1 truncate">{{ $resolvedWelcome }}</p>
                    </div>

                    <div class="mx-auto hidden max-w-md flex-1 px-4 md:flex">
                        <label class="relative flex w-full items-center">
                            <span class="pointer-events-none absolute left-4 text-[var(--text-muted)]">
                                <i data-lucide="search" class="h-[18px] w-[18px]"></i>
                            </span>
                            <input
                                type="search"
                                placeholder="Search intelligence, entities, signals…"
                                class="w-full rounded-full border border-[rgba(255,255,255,0.045)] bg-[rgba(16,16,16,0.85)] py-2.5 pl-11 pr-24 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-muted)] shadow-mom-inner outline-none ring-offset-0 transition-all duration-320 ease-premium focus:border-[rgba(212,169,95,0.28)] focus:shadow-[0_0_24px_rgba(212,169,95,0.12)]"
                            />
                            <kbd
                                class="pointer-events-none absolute right-3 hidden rounded-md border border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.03)] px-2 py-0.5 font-mono text-[11px] text-[var(--text-muted)] sm:inline-block"
                            >⌘ K</kbd>
                        </label>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <button
                            type="button"
                            class="hidden rounded-full border border-[rgba(255,255,255,0.045)] px-4 py-2 text-xs font-semibold uppercase tracking-wide text-mom-gold shadow-mom-inner transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.28)] hover:shadow-[0_0_28px_rgba(212,169,95,0.1)] md:inline-flex"
                        >
                            Suspend System
                        </button>
                        <button
                            type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-[rgba(255,255,255,0.045)] text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)]"
                            aria-label="Notifications"
                        >
                            <i data-lucide="bell" class="h-[18px] w-[18px]"></i>
                        </button>
                        <button
                            type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-[rgba(255,255,255,0.045)] text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)]"
                            aria-label="Theme"
                        >
                            <i data-lucide="moon" class="h-[18px] w-[18px]"></i>
                        </button>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-[rgba(212,169,95,0.22)] bg-[rgba(212,169,95,0.08)] text-xs font-semibold text-mom-gold"
                            aria-hidden="true"
                        >
                            {{ $initials ?: 'AU' }}
                        </div>
                    </div>
                </header>

                <main class="flex-1 px-8 py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
