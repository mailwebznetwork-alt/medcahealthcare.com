@php
    $checks = [
        ['label' => __('Application debug mode'), 'value' => $debug ? __('On') : __('Off'), 'ok' => ! $debug || app()->environment('local')],
        ['label' => __('Database'), 'value' => $databaseConnected ? __('Reachable') : __('Unreachable'), 'ok' => $databaseConnected],
        ['label' => __('Queue connection'), 'value' => (string) config('queue.default'), 'ok' => true],
    ];
@endphp

<x-system.shell active-section="health" :welcome-line="__('Read-only platform health signals.')">
    <article class="mom-card p-6">
        <h2 class="mom-section-title">{{ __('System health') }}</h2>
        <ul class="mt-4 space-y-3">
            @foreach ($checks as $check)
                <li class="flex items-center justify-between rounded-lg border border-[var(--border-panel-soft)] px-4 py-3">
                    <span class="text-sm text-[var(--text-primary)]">{{ $check['label'] }}</span>
                    <span @class([
                        'text-sm font-semibold',
                        'text-[var(--success)]' => $check['ok'],
                        'text-[var(--danger)]' => ! $check['ok'],
                    ])>{{ $check['value'] }}</span>
                </li>
            @endforeach
        </ul>
    </article>
</x-system.shell>
