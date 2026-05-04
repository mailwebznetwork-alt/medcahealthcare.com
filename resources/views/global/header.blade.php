@php
    $nav = [
        ['label' => __('HOME'), 'href' => url('/')],
        ['label' => __('ABOUT'), 'href' => url('/#about')],
        ['label' => __('SERVICES'), 'href' => url('/#services')],
        ['label' => __('LOCATIONS'), 'href' => url('/#locations')],
        ['label' => __('CAREERS'), 'href' => route('careers.index')],
        ['label' => __('CONTACT'), 'href' => url('/#contact')],
    ];
    $navMobile = [
        ['label' => __('HOME'), 'href' => url('/')],
        ['label' => __('ABOUT US'), 'href' => url('/#about')],
        ['label' => __('SERVICES'), 'href' => url('/#services')],
        ['label' => __('LOCATIONS'), 'href' => url('/#locations')],
        ['label' => __('CAREERS'), 'href' => route('careers.index')],
        ['label' => __('CONTACT US'), 'href' => url('/#contact')],
    ];
@endphp

<header class="sticky top-0 z-40 border-b border-[rgba(255,255,255,0.06)] bg-[var(--bg-surface)]">
    <div class="border-b border-[rgba(255,255,255,0.04)] bg-[var(--bg-sidebar)] px-4 py-2 text-xs text-[var(--text-secondary)] sm:px-6">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2">
            <span>{{ config('medca.top_bar_claim') }}</span>
            <span class="text-[var(--text-muted)]">{{ config('medca.location_display') }}</span>
        </div>
    </div>

    <div
        class="mx-auto max-w-6xl px-4 py-4 sm:px-6"
        x-data="{ navOpen: false }"
        @keydown.escape.window="navOpen = false"
    >
        <div class="flex items-center justify-between gap-4">
            <a href="{{ url('/') }}" class="group flex min-w-0 flex-1 items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[var(--radius-chrome)] border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] text-sm font-bold text-[var(--accent-gold)]">
                    MH
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-base font-semibold text-[var(--text-primary)]">{{ config('medca.brand_name') }}</span>
                    <span class="block truncate text-xs text-[var(--text-muted)]">{{ config('medca.tagline') }}</span>
                </span>
            </a>

            <nav class="hidden items-center gap-1 md:flex" aria-label="{{ __('Primary') }}">
                @foreach ($nav as $item)
                    <a
                        href="{{ $item['href'] }}"
                        class="rounded-[var(--radius-cta)] px-3 py-2 text-xs font-semibold uppercase tracking-wide text-[var(--text-secondary)] transition hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <button
                type="button"
                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[var(--radius-chrome)] border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] text-[var(--text-primary)] md:hidden"
                @click="navOpen = true"
                :aria-expanded="navOpen"
                aria-controls="medca-mobile-nav"
                aria-label="{{ __('Open menu') }}"
            >
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
        </div>

        <div
            x-show="navOpen"
            x-cloak
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[100] md:hidden"
            id="medca-mobile-nav"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('Navigation') }}"
        >
            <div class="absolute inset-0 bg-black/60 backdrop-blur-[2px]" @click="navOpen = false"></div>
            <div
                x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col border-l border-[var(--border-panel)] bg-[var(--bg-elevated)] shadow-2xl"
            >
                <div class="flex items-center justify-between border-b border-[var(--border-panel-soft)] px-4 py-4">
                    <p class="text-sm font-semibold text-[var(--text-primary)]">{{ __('Medca Navigation') }}</p>
                    <button
                        type="button"
                        class="rounded-[var(--radius-cta)] p-2 text-[var(--text-secondary)] hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]"
                        @click="navOpen = false"
                        aria-label="{{ __('Close menu') }}"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="border-b border-[var(--border-panel-soft)] px-4 py-3">
                    <label class="sr-only" for="medca-nav-search">{{ __('Search services') }}</label>
                    <input
                        id="medca-nav-search"
                        type="search"
                        placeholder="{{ __('Search services...') }}"
                        class="w-full rounded-[var(--radius-chrome)] border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2.5 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-muted)]"
                    />
                </div>
                <nav class="flex-1 overflow-y-auto px-2 py-4" aria-label="{{ __('Mobile primary') }}">
                    <ul class="space-y-1">
                        @foreach ($navMobile as $item)
                            <li>
                                <a
                                    href="{{ $item['href'] }}"
                                    class="block rounded-[var(--radius-chrome)] px-3 py-3 text-sm font-semibold uppercase tracking-wide text-[var(--text-secondary)] hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]"
                                    @click="navOpen = false"
                                >
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</header>
