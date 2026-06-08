@php
    $tabs = \App\Support\SiteArchitectNavigation::tabGroups();
    $groupHints = \App\Support\SiteArchitectUxCopy::tabGroupHints();
@endphp

<nav class="space-y-4" aria-label="{{ __('Site Architect workspaces') }}">
    @foreach ($tabs as $group)
        <div>
            <p class="mom-micro mb-1 px-1">{{ $group['label'] }}</p>
            @if (! empty($groupHints[$group['key'] ?? ''] ?? null))
                <p class="mb-2 px-1 text-[11px] leading-snug text-[var(--text-muted)]">{{ $groupHints[$group['key']] }}</p>
            @endif
            <div class="flex flex-wrap gap-0 border-b border-[color:var(--border-tabstrip-divider)]">
                @foreach ($group['items'] as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'inline-flex items-center border-b px-4 py-3 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
                            'border-mom-gold text-mom-gold' => $item['active'],
                            'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => ! $item['active'],
                        ])
                    >
                        {{ $item['label'] }}
                        @if (! empty($item['legacy']))
                            <span class="ml-1.5 rounded bg-[var(--bg-elevated)] px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Legacy') }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
</nav>
