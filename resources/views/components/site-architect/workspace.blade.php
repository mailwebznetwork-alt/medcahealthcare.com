@props([
    'pageTitle' => null,
    'welcomeLine' => null,
])

@php
    $resolvedPageTitle = $pageTitle ?? __('Site Architect');
    $resolvedWelcome = $welcomeLine ?? \App\Support\SiteArchitectUxCopy::workspaceWelcome();
    $showComposeJourney = request()->routeIs(
        'site-architect.pages.*',
        'site-architect.block-studio.*',
        'site-architect.block-factory.*',
        'site-architect.block-presets.*',
        'site-architect.presets.*',
    );
    $sidebarGroups = \App\Support\SiteArchitectSidebarState::groups();
    $defaultExpanded = \App\Support\SiteArchitectSidebarState::defaultExpanded();
@endphp

<x-app-layout
    :page-title="$resolvedPageTitle"
    :welcome-line="$resolvedWelcome"
>
    <div
        class="site-architect-workspace"
        x-data="{
            mobileOpen: false,
            expanded: (() => {
                const defaults = @js(array_fill_keys($defaultExpanded, true));
                try {
                    const stored = JSON.parse(localStorage.getItem('medca.site-architect.sidebar.expanded') || 'null');
                    return stored && typeof stored === 'object' ? { ...defaults, ...stored } : defaults;
                } catch (e) { return defaults; }
            })(),
            toggleGroup(key) {
                this.expanded[key] = !this.isExpanded(key);
                try { localStorage.setItem('medca.site-architect.sidebar.expanded', JSON.stringify(this.expanded)); } catch (e) {}
            },
            isExpanded(key) { return this.expanded[key] !== false; },
        }"
    >
        <div class="mb-4 flex items-center justify-between gap-3 lg:hidden">
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2 text-sm font-semibold text-[var(--text-primary)]"
                x-on:click="mobileOpen = true"
                aria-controls="site-architect-sidebar"
                :aria-expanded="mobileOpen"
            >
                <span class="font-mono text-base leading-none" aria-hidden="true">☰</span>
                {{ __('Site Architect menu') }}
            </button>
        </div>

        <div
            class="fixed inset-0 z-40 bg-black/55 lg:hidden"
            x-show="mobileOpen"
            x-transition.opacity
            style="display:none;"
            x-on:click="mobileOpen = false"
        ></div>

        <div class="flex min-h-0 flex-col gap-6 lg:flex-row lg:items-start">
            <div
                id="site-architect-sidebar"
                class="site-architect-sidebar mom-card fixed inset-y-0 left-0 z-50 flex w-[min(18rem,88vw)] -translate-x-full flex-col border-[var(--border-panel-soft)] pt-[72px] transition-transform duration-320 ease-premium lg:static lg:z-auto lg:w-56 lg:translate-x-0 lg:pt-0 xl:w-60"
                :class="{ '!translate-x-0': mobileOpen }"
            >
                <div class="flex items-center justify-between border-b border-[var(--border-panel-soft)] px-4 py-3 lg:hidden">
                    <p class="text-sm font-semibold text-[var(--text-primary)]">{{ __('Site Architect') }}</p>
                    <button type="button" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-2 py-1 text-xs" x-on:click="mobileOpen = false">{{ __('Close') }}</button>
                </div>

                <div class="custom-scrollbar flex-1 overflow-y-auto px-2 py-3 lg:sticky lg:top-[88px] lg:max-h-[calc(100dvh-88px)]">
                    <p class="mom-micro mb-3 hidden px-2 lg:block">{{ __('Site Architect') }}</p>

                    <nav class="space-y-1" aria-label="{{ __('Site Architect navigation') }}">
                        @foreach ($sidebarGroups as $group)
                            <div class="rounded-mom-chrome">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between gap-2 rounded-mom-chrome px-3 py-2 text-left text-sm font-semibold text-[var(--text-primary)] hover:bg-[var(--bg-hover)]"
                                    x-on:click="toggleGroup(@js($group['key']))"
                                    :aria-expanded="isExpanded(@js($group['key']))"
                                >
                                    <span>{{ $group['label'] }}</span>
                                    <span class="font-mono text-xs text-[var(--text-muted)]" x-text="isExpanded(@js($group['key'])) ? '−' : '+'"></span>
                                </button>

                                <ul class="space-y-0.5 pb-2 pl-1" x-show="isExpanded(@js($group['key']))" x-cloak>
                                    @foreach ($group['items'] as $item)
                                        @php
                                            $href = route($item['route'], $item['query'] ?? []);
                                            if (! empty($item['fragment'] ?? null)) {
                                                $href .= '#'.$item['fragment'];
                                            }
                                        @endphp
                                        <li>
                                            <a
                                                href="{{ $href }}"
                                                @class([
                                                    'flex items-center gap-2 rounded-mom-chrome px-3 py-2 text-sm transition-colors',
                                                    'bg-[rgba(197,160,89,0.12)] font-semibold text-mom-gold' => $item['active'],
                                                    'text-[var(--text-secondary)] hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]' => ! $item['active'],
                                                ])
                                                x-on:click="mobileOpen = false"
                                            >
                                                <span>{{ $item['label'] }}</span>
                                                @if (! empty($item['legacy']))
                                                    <span class="rounded bg-[var(--bg-elevated)] px-1 py-0.5 text-[9px] font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Legacy') }}</span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </nav>
                </div>
            </div>

            <div class="min-w-0 flex-1">
                @if ($showComposeJourney)
                    @include('site-architect.partials.compose-journey', ['compact' => ! request()->routeIs('site-architect.pages.index')])
                @endif

                {{ $slot }}
            </div>
        </div>
    </div>

    @livewire('media.media-picker-modal')
</x-app-layout>

@push('head')
    <style>
        .site-architect-sidebar .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .site-architect-sidebar .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
    </style>
@endpush
