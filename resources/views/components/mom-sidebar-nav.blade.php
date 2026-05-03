@props([
    'user',
])

@php
    /** @var \App\Models\User $user */
    $nodes = $user->visibleSidebarNodes();
@endphp

<nav
    class="mom-sidebar-nav-scroll flex min-h-0 flex-1 flex-col gap-0 overflow-y-auto px-4 py-8"
    role="navigation"
    aria-label="{{ __('Application') }}"
    data-mom-nav-root
>
    <ul class="space-y-1">
        @foreach ($nodes as $node)
            @if ($node['type'] === 'link' && $node['key'] === 'settings')
                <li class="list-none px-3 py-2" aria-hidden="true">
                    <hr class="mom-nav-divider mom-nav-divider--sidebar" />
                </li>
            @endif

            @if ($node['type'] === 'link')
                @php
                    $active = $node['key'] === \App\ModuleAccess::OPERATIONS
                        ? request()->routeIs('modules.operations', 'operations.job-portal.*')
                        : request()->routeIs($node['route']);
                @endphp
                <li>
                    <a
                        href="{{ route($node['route']) }}"
                        @class([
                            'mom-nav-active flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-mom-gold transition-all duration-320 ease-premium' => $active,
                            'flex items-center gap-3 rounded-full px-3 py-2.5 text-sm font-medium text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)] hover:shadow-[0_0_22px_rgba(212,169,95,0.06)]' => ! $active,
                        ])
                    >
                        <i
                            data-lucide="{{ $node['icon'] }}"
                            class="h-[18px] w-[18px] shrink-0 {{ $active ? '' : 'opacity-80' }}"
                        ></i>
                        <span>{{ __($node['label']) }}</span>
                    </a>
                </li>
                @if ($node['key'] === 'dashboard')
                    <li class="list-none px-3 py-2" aria-hidden="true">
                        <hr class="mom-nav-divider mom-nav-divider--sidebar" />
                    </li>
                @endif
                @if ($node['key'] === \App\ModuleAccess::OPERATIONS)
                    <li class="list-none px-3 py-2" aria-hidden="true">
                        <hr class="mom-nav-divider mom-nav-divider--sidebar" />
                    </li>
                @endif
            @else
                <li class="pt-2 first:pt-0">
                    <p class="mom-micro mb-2 px-3">{{ __($node['label']) }}</p>
                    <ul class="space-y-1 border-l border-[rgba(255,255,255,0.06)] pl-3" role="group" aria-label="{{ __($node['label']) }}">
                        @foreach ($node['children'] as $child)
                            @php
                                $childActive = isset($child['pattern'])
                                    ? request()->routeIs($child['pattern'])
                                    : request()->routeIs($child['route']);
                            @endphp
                            <li>
                                <a
                                    href="{{ route($child['route']) }}"
                                    @class([
                                        'mom-nav-active flex items-center gap-3 rounded-r-full px-3 py-2 text-sm font-medium text-mom-gold transition-all duration-320 ease-premium' => $childActive,
                                        'flex items-center gap-3 rounded-r-full px-3 py-2 text-sm font-medium text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]' => ! $childActive,
                                    ])
                                >
                                    <i
                                        data-lucide="{{ $child['icon'] }}"
                                        class="h-[17px] w-[17px] shrink-0 {{ $childActive ? '' : 'opacity-80' }}"
                                    ></i>
                                    <span>{{ __($child['label']) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endif
        @endforeach
    </ul>
</nav>
