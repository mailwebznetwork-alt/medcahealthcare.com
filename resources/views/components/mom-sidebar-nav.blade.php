@props([
    'user',
])

@php
    /** @var \App\Models\User $user */
    use App\ModuleAccess;

    $nodes = $user->visibleSidebarNodes();
    $nodesByKey = collect($nodes)->keyBy('key');

    /** Logical order for flat nav + future keys: add rows here — separators appear between every adjacent pair. */
    $sectionKeys = [
        [
            ModuleAccess::DASHBOARD,
            ModuleAccess::SITE_ARCHITECT,
            ModuleAccess::OPERATIONS,
        ],
        [
            ModuleAccess::MARKETING,
            ModuleAccess::GROWTH_CENTER,
        ],
        [
            ModuleAccess::USER_MANAGEMENT,
            ModuleAccess::SECURITY,
        ],
        [
            ModuleAccess::SETTINGS,
        ],
    ];

    $assignedKeys = collect($sectionKeys)->flatten()->all();

    $orderedLinks = [];
    foreach ($sectionKeys as $keys) {
        foreach ($keys as $key) {
            $node = $nodesByKey->get($key);
            if ($node !== null && $node['type'] === 'link') {
                $orderedLinks[] = $node;
            }
        }
    }

    foreach ($nodes as $node) {
        if ($node['type'] === 'link' && ! in_array($node['key'], $assignedKeys, true)) {
            $orderedLinks[] = $node;
        }
    }
@endphp

<nav
    class="mom-sidebar-nav-scroll flex min-h-0 flex-1 flex-col overflow-y-auto px-3 py-6"
    role="navigation"
    aria-label="{{ __('Application') }}"
    data-mom-nav-root
>
    @if ($orderedLinks !== [])
        <ul
            class="mom-sidebar-nav-list flex flex-col divide-y divide-[color:var(--border-tabstrip-divider)]"
            role="list"
        >
            @foreach ($orderedLinks as $navNode)
                @php
                    $active = $navNode['key'] === ModuleAccess::OPERATIONS
                        ? request()->routeIs('modules.operations', 'operations.job-portal.*', 'operations.pin-codes.*')
                        : request()->routeIs($navNode['route']);
                @endphp
                <li class="list-none py-3 first:pt-0 last:pb-0">
                    <a
                        href="{{ route($navNode['route']) }}"
                        @class([
                            'mom-sidebar-link mom-nav-active text-mom-gold transition-all duration-320 ease-premium' => $active,
                            'mom-sidebar-link text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)] hover:shadow-[0_0_22px_rgba(197,160,89,0.06)]' => ! $active,
                        ])
                    >
                        <span class="mom-sidebar-link__icon {{ $active ? '' : 'opacity-80' }}" aria-hidden="true">
                            <i data-lucide="{{ $navNode['icon'] }}"></i>
                        </span>
                        <span class="mom-sidebar-link__label truncate">{{ __($navNode['label']) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @foreach ($nodes as $navNode)
        @if ($navNode['type'] !== 'group')
            @continue
        @endif
        <div
            @class([
                'mt-6 border-t border-[color:var(--border-tabstrip-divider)] pt-6' => $orderedLinks !== [],
                'mt-0' => $orderedLinks === [],
            ])
        >
            <p class="mom-micro mb-3 px-3">{{ __($navNode['label']) }}</p>
            <ul
                class="mom-sidebar-nav-list flex flex-col divide-y divide-[color:var(--border-tabstrip-divider)]"
                role="group"
                aria-label="{{ __($navNode['label']) }}"
            >
                @foreach ($navNode['children'] as $child)
                    @php
                        $childActive = isset($child['pattern'])
                            ? request()->routeIs($child['pattern'])
                            : request()->routeIs($child['route']);
                    @endphp
                    <li class="list-none py-3 first:pt-0 last:pb-0">
                        <a
                            href="{{ route($child['route']) }}"
                            @class([
                                'mom-sidebar-link mom-nav-active text-mom-gold transition-all duration-320 ease-premium' => $childActive,
                                'mom-sidebar-link text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]' => ! $childActive,
                            ])
                        >
                            <span class="mom-sidebar-link__icon {{ $childActive ? '' : 'opacity-80' }}" aria-hidden="true">
                                <i data-lucide="{{ $child['icon'] }}"></i>
                            </span>
                            <span class="mom-sidebar-link__label truncate">{{ __($child['label']) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
