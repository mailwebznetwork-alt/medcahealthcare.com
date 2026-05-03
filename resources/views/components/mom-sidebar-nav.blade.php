@props([
    'user',
])

@php
    /** @var \App\Models\User $user */
    $items = $user->visibleNavigation();
@endphp

<nav
    class="mom-sidebar-nav-scroll flex min-h-0 flex-1 flex-col gap-0 overflow-y-auto px-4 py-8"
    role="navigation"
    aria-label="{{ __('Application') }}"
    data-mom-nav-root
>
    <ul class="space-y-1">
        @foreach ($items as $item)
            @php
                $active = request()->routeIs($item['route']);
            @endphp
            <li>
                <a
                    href="{{ route($item['route']) }}"
                    @class([
                        'mom-nav-active flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-mom-gold transition-all duration-320 ease-premium' => $active,
                        'flex items-center gap-3 rounded-full px-3 py-2.5 text-sm font-medium text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)] hover:shadow-[0_0_22px_rgba(212,169,95,0.06)]' => ! $active,
                    ])
                >
                    <i
                        data-lucide="{{ $item['icon'] }}"
                        class="h-[18px] w-[18px] shrink-0 {{ $active ? '' : 'opacity-80' }}"
                    ></i>
                    <span>{{ __($item['label']) }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
