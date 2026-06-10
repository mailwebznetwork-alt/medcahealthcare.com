@props([
    'item',
    'navLinkBase' => '',
    'navLinkDefault' => '',
    'navLinkActive' => '',
    'isMobile' => false,
    'showBorder' => false,
    'depth' => 0,
])

@php
    $children = is_array($item['children'] ?? null) ? $item['children'] : [];
    $href = $item['href'] ?? null;
    $label = $item['label'] ?? '';
    $hasChildren = $children !== [];
    $isCurrent = $href ? \App\Support\PublicNav::isCurrent($href) : false;
@endphp

@if ($isMobile)
    @php
        $mobileRowStyle = 'padding-left: '.(0.25 + ($depth * 1)).'rem';
        $mobileRowClass = 'flex w-full min-h-[52px] items-center gap-3 border-b border-slate-100 pr-1 text-sm font-medium uppercase tracking-[0.05em] text-medca-primary';
    @endphp
    @if ($hasChildren)
        <div x-data="{ open: false }">
            <button
                type="button"
                @click="open = !open"
                class="{{ $mobileRowClass }} justify-between text-left"
                style="{{ $mobileRowStyle }}"
            >
                <span class="min-w-0 flex-1 leading-snug">{{ $label }}</span>
                <span class="shrink-0 text-base leading-none" x-text="open ? '−' : '+'" aria-hidden="true"></span>
            </button>
            <div x-show="open" x-cloak class="pb-1">
                @foreach ($children as $child)
                    <x-public.nav-item :item="$child" :is-mobile="true" :depth="$depth + 1" />
                @endforeach
            </div>
        </div>
    @else
        <a
            href="{{ $href ?? '#' }}"
            @if ($isCurrent) aria-current="page" @endif
            class="{{ $mobileRowClass }} {{ $isCurrent ? $navLinkActive : 'hover:text-medca-primary-hover' }}"
            style="{{ $mobileRowStyle }}"
        >
            <span class="min-w-0 flex-1 leading-snug">{{ $label }}</span>
        </a>
    @endif
@else
    <li @class([
        'medca-nav-item-dropdown flex shrink-0 items-center px-2 lg:px-2.5',
        'border-l border-solid border-slate-300' => $showBorder,
    ])>
        @if ($hasChildren)
            <button
                type="button"
                class="{{ $navLinkBase }} {{ $navLinkDefault }} gap-1"
                aria-haspopup="true"
                aria-expanded="false"
            >
                {{ $label }}
                <svg class="h-3 w-3 opacity-70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/></svg>
            </button>
            <ul class="medca-nav-dropdown" role="menu">
                @foreach ($children as $child)
                    @include('components.public.nav-dropdown-item', ['item' => $child, 'depth' => 0])
                @endforeach
            </ul>
        @else
            <a
                href="{{ $href ?? '#' }}"
                @if ($isCurrent) aria-current="page" @endif
                class="{{ $navLinkBase }} {{ $isCurrent ? $navLinkActive : $navLinkDefault }}"
            >
                {{ $label }}
            </a>
        @endif
    </li>
@endif
