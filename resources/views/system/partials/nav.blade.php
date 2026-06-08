@php
    $active = $activeSection ?? 'overview';
@endphp

<nav class="flex flex-wrap gap-0" aria-label="{{ __('System sections') }}">
    @foreach ([
        'overview' => [__('Overview'), 'system.index'],
        'source-of-truth' => [__('Source of Truth'), 'system.source-of-truth'],
        'integrations' => [__('Integrations'), 'settings.integrations'],
        'webhooks' => [__('Webhooks'), 'settings.webhooks'],
        'queue' => [__('Queue'), 'system.queue'],
        'scheduler' => [__('Scheduler'), 'system.scheduler'],
        'health' => [__('Health'), 'system.health'],
    ] as $key => [$label, $routeName])
        <a
            href="{{ route($routeName) }}"
            @class([
                'inline-flex items-center border-b px-5 py-3.5 text-sm font-semibold tracking-wide transition-colors duration-320 ease-premium',
                'border-mom-gold text-mom-gold' => $active === $key,
                'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-panel-soft)] hover:text-[var(--text-primary)]' => $active !== $key,
            ])
        >{{ $label }}</a>
    @endforeach
</nav>
