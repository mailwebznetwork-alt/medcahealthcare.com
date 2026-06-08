@props([
    'user',
    'moduleKey',
])

@php
    /** @var \App\Models\User $user */
    use App\Support\AdminNavigation;
    use App\Support\ModuleSidebarNavigation;

    $meta = ModuleSidebarNavigation::meta($moduleKey);
    $groups = ModuleSidebarNavigation::sidebarGroups($moduleKey, $user);
    $defaultExpanded = ModuleSidebarNavigation::defaultExpanded($moduleKey);
    $moduleActive = AdminNavigation::isNavActive($moduleKey);
    $storageKey = 'medca.sidebar.'.$moduleKey;
@endphp

<div
    class="mom-sidebar-module"
    data-sidebar-module="{{ $moduleKey }}"
    x-data="{
        open: @js($moduleActive),
        expanded: (() => {
            const defaults = @js(array_fill_keys($defaultExpanded, true));
            try {
                const stored = JSON.parse(localStorage.getItem(@js($storageKey.'.expanded')) || 'null');
                return stored && typeof stored === 'object' ? { ...defaults, ...stored } : defaults;
            } catch (e) { return defaults; }
        })(),
        toggleOpen() { this.open = ! this.open; },
        toggleGroup(key) {
            this.expanded[key] = ! this.isExpanded(key);
            try { localStorage.setItem(@js($storageKey.'.expanded'), JSON.stringify(this.expanded)); } catch (e) {}
        },
        isExpanded(key) { return this.expanded[key] !== false; },
    }"
>
    <div class="flex items-center gap-1">
        <a
            href="{{ route($meta['homeRoute']) }}"
            @class([
                'mom-sidebar-link min-w-0 flex-1 mom-nav-active text-mom-gold transition-all duration-320 ease-premium' => $moduleActive,
                'mom-sidebar-link min-w-0 flex-1 text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]' => ! $moduleActive,
            ])
            @click="mobileNav = false"
        >
            <span class="mom-sidebar-link__icon {{ $moduleActive ? '' : 'opacity-80' }}" aria-hidden="true">
                <i data-lucide="{{ $meta['icon'] }}"></i>
            </span>
            <span class="mom-sidebar-link__label truncate">{{ __($meta['label']) }}</span>
        </a>
        <button
            type="button"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-mom-chrome text-[var(--text-muted)] transition-colors hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]"
            x-on:click="toggleOpen()"
            :aria-expanded="open"
            aria-label="{{ __('Toggle :module menu', ['module' => __($meta['label'])]) }}"
        >
            <span class="font-mono text-xs" x-text="open ? '−' : '+'"></span>
        </button>
    </div>

    <div class="mt-1 space-y-2 pl-3" x-show="open" x-cloak>
        @foreach ($groups as $group)
            <div class="rounded-mom-chrome">
                <button
                    type="button"
                    class="mom-sidebar-module__group-label flex w-full items-center justify-between gap-2 rounded-mom-chrome px-2 py-1.5 text-left hover:bg-[var(--bg-hover)] hover:text-[var(--text-secondary)]"
                    x-on:click="toggleGroup(@js($group['key']))"
                    :aria-expanded="isExpanded(@js($group['key']))"
                >
                    <span>{{ $group['label'] }}</span>
                    <span class="font-mono text-[10px]" x-text="isExpanded(@js($group['key'])) ? '−' : '+'"></span>
                </button>

                <ul class="space-y-0.5 py-1" x-show="isExpanded(@js($group['key']))" x-cloak>
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
                                    'mom-sidebar-module__child-link block rounded-mom-chrome px-3 py-2 transition-colors hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]',
                                    'is-active' => $item['active'],
                                ])
                                @click="mobileNav = false"
                            >
                                <span class="flex items-center gap-2">
                                    <span>{{ $item['label'] }}</span>
                                    @if (! empty($item['legacy']))
                                        <span class="rounded bg-[var(--bg-elevated)] px-1 py-0.5 text-[9px] font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Legacy') }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>
