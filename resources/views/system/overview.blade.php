<x-system.shell active-section="overview">
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        <a href="{{ \App\Support\AdminMetricLinks::systemOverview() }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
            <p class="mom-micro">{{ __('Application') }}</p>
            <p class="mom-body-text mt-2 font-semibold text-[var(--text-primary)]">{{ $appName }}</p>
            <p class="mom-subtext mt-1">{{ __('Environment') }}: {{ $environment }}</p>
        </a>
        <a href="{{ \App\Support\AdminMetricLinks::systemOverview() }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
            <p class="mom-micro">{{ __('Database') }}</p>
            <p class="mom-metric mt-2 leading-none">{{ $databaseConnected ? __('Connected') : __('Unavailable') }}</p>
        </a>
        <a href="{{ \App\Support\AdminMetricLinks::systemQueue() }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
            <p class="mom-micro">{{ __('Queue') }}</p>
            <p class="mom-body-text mt-2 font-semibold text-[var(--text-primary)]">{{ $queueConnection }}</p>
            <p class="mom-subtext mt-1">{{ __('Driver') }}: {{ is_string($queueDriver) ? $queueDriver : '—' }}</p>
            @if ($failedJobsCount !== null)
                <p class="mom-subtext mt-1">{{ __('Failed jobs') }}: {{ number_format($failedJobsCount) }}</p>
            @endif
        </a>
    </div>

    <p class="mom-body-text mt-8 text-[var(--text-secondary)]">
        {{ __('Configure third-party services under Integrations. Operational theme and content variables remain under Settings.') }}
    </p>
</x-system.shell>
