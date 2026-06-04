@php
    $steps = \App\Support\SiteArchitectNavigation::deploymentShortcutSteps();
@endphp

@if (count($steps) > 0 && \App\Support\SiteArchitectNavigation::shouldShowDeploymentShortcuts())
    <nav class="mb-6 rounded-xl border border-[var(--border-panel-soft)] bg-[var(--bg-surface)] p-4" aria-label="{{ __('Deploy shortcuts') }}">
        <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-mom-gold">{{ __('Deploy shortcuts') }}</p>
        <p class="mom-subtext mb-3 text-[11px]">{{ __('Quick links for site setup — primary workspaces are in the tabs above.') }}</p>
        <ol class="flex flex-wrap gap-2">
            @foreach ($steps as $step)
                <li>
                    <a
                        href="{{ route($step['route']) }}"
                        @class([
                            'inline-flex flex-col rounded-lg border px-3 py-2 text-xs transition',
                            'border-mom-gold bg-mom-gold/10 text-mom-gold' => request()->routeIs($step['route'].'*') || request()->routeIs($step['route']),
                            'border-[var(--border-panel-soft)] text-[var(--text-secondary)] hover:border-mom-gold/50 hover:text-[var(--text-primary)]' => ! request()->routeIs($step['route'].'*') && ! request()->routeIs($step['route']),
                        ])
                    >
                        <span class="font-semibold">{{ $step['label'] }}</span>
                        <span class="opacity-80">{{ $step['hint'] }}</span>
                    </a>
                </li>
            @endforeach
        </ol>
    </nav>
@endif
