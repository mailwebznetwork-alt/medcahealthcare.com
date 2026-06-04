<x-system.shell active-section="queue" :welcome-line="__('Queue connection and failed job visibility (read-only).')">
    <article class="mom-card p-6">
        <h2 class="mom-section-title">{{ __('Queue configuration') }}</h2>
        <dl class="mom-body-text mt-4 grid gap-3 sm:grid-cols-2">
            <div>
                <dt class="text-[var(--text-muted)]">{{ __('Default connection') }}</dt>
                <dd class="font-mono text-[var(--text-primary)]">{{ config('queue.default') }}</dd>
            </div>
            <div>
                <dt class="text-[var(--text-muted)]">{{ __('Driver') }}</dt>
                <dd class="font-mono text-[var(--text-primary)]">{{ config('queue.connections.'.config('queue.default').'.driver') ?? '—' }}</dd>
            </div>
        </dl>
        <p class="mom-subtext mt-4">{{ __('Job processing uses Laravel queues. Change connection in environment configuration.') }}</p>
    </article>
</x-system.shell>
