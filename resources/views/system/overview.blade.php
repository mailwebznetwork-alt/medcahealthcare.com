<x-system.shell active-section="overview">
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        <article class="mom-card px-5 py-4">
            <p class="mom-micro">{{ __('Application') }}</p>
            <p class="mom-body-text mt-2 font-semibold text-[var(--text-primary)]">{{ $appName }}</p>
            <p class="mom-subtext mt-1">{{ __('Environment') }}: {{ $environment }}</p>
        </article>
        <article class="mom-card px-5 py-4">
            <p class="mom-micro">{{ __('Database') }}</p>
            <p class="mom-metric mt-2 leading-none">{{ $databaseConnected ? __('Connected') : __('Unavailable') }}</p>
        </article>
        <article class="mom-card px-5 py-4">
            <p class="mom-micro">{{ __('Queue') }}</p>
            <p class="mom-body-text mt-2 font-semibold text-[var(--text-primary)]">{{ $queueConnection }}</p>
            <p class="mom-subtext mt-1">{{ __('Driver') }}: {{ is_string($queueDriver) ? $queueDriver : '—' }}</p>
            @if ($failedJobsCount !== null)
                <p class="mom-subtext mt-1">{{ __('Failed jobs') }}: {{ number_format($failedJobsCount) }}</p>
            @endif
        </article>
    </div>

    <p class="mom-body-text mt-8 text-[var(--text-secondary)]">
        {{ __('Configure third-party services under Integrations. Operational theme and content variables remain under Settings.') }}
    </p>
</x-system.shell>
