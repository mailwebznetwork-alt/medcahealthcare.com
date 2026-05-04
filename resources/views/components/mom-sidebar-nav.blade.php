@props([
    'user',
])

@php
    /** @var \App\Models\User $user */
    use App\ModuleAccess;

    $nodes = $user->visibleSidebarNodes();
    $nodesByKey = collect($nodes)->keyBy('key');

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
@endphp

<nav
    class="mom-sidebar-nav-scroll flex min-h-0 flex-1 flex-col overflow-y-auto px-3 py-6"
    role="navigation"
    aria-label="{{ __('Application') }}"
    data-mom-nav-root
>
    @php $sectionRendered = false; @endphp
    @foreach ($sectionKeys as $keys)
        @php
            $sectionNodes = collect($keys)
                ->map(fn (string $key) => $nodesByKey->get($key))
                ->filter()
                ->values();
        @endphp
        @if ($sectionNodes->isEmpty())
            @continue
        @endif

        @if ($sectionRendered)
            <div class="mom-sidebar-section-divider" role="presentation" aria-hidden="true"></div>
        @endif

        <ul class="flex flex-col gap-y-3" role="list">
            @foreach ($sectionNodes as $navNode)
                @if ($navNode['type'] !== 'link')
                    @continue
                @endif

                @php
                    $active = $navNode['key'] === ModuleAccess::OPERATIONS
                        ? request()->routeIs('modules.operations', 'operations.job-portal.*', 'operations.pin-codes.*')
                        : request()->routeIs($navNode['route']);
                @endphp
                <li class="list-none">
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

        @php $sectionRendered = true; @endphp
    @endforeach

    @php
        $orphanLinks = collect($nodes)->filter(
            fn (array $n) => $n['type'] === 'link' && ! in_array($n['key'], $assignedKeys, true)
        );
    @endphp
    @if ($orphanLinks->isNotEmpty())
        @if ($sectionRendered)
            <div class="mom-sidebar-section-divider" role="presentation" aria-hidden="true"></div>
        @endif
        <ul class="flex flex-col gap-y-3" role="list">
            @foreach ($orphanLinks as $navNode)
                @php
                    $active = $navNode['key'] === ModuleAccess::OPERATIONS
                        ? request()->routeIs('modules.operations', 'operations.job-portal.*', 'operations.pin-codes.*')
                        : request()->routeIs($navNode['route']);
                @endphp
                <li class="list-none">
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
        @php $sectionRendered = true; @endphp
    @endif

    @foreach ($nodes as $navNode)
        @if ($navNode['type'] !== 'group')
            @continue
        @endif
        @if ($sectionRendered)
            <div class="mom-sidebar-section-divider" role="presentation" aria-hidden="true"></div>
        @endif
        <div class="flex flex-col gap-y-3">
            <p class="mom-micro px-3">{{ __($navNode['label']) }}</p>
            <ul class="flex flex-col gap-y-3" role="group" aria-label="{{ __($navNode['label']) }}">
                @foreach ($navNode['children'] as $child)
                    @php
                        $childActive = isset($child['pattern'])
                            ? request()->routeIs($child['pattern'])
                            : request()->routeIs($child['route']);
                    @endphp
                    <li class="list-none">
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
        @php $sectionRendered = true; @endphp
    @endforeach
</nav>
