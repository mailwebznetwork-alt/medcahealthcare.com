<div class="space-y-6">
    @if ($flash)
        <div class="rounded-mom-chrome border border-[rgba(197,160,89,0.25)] bg-[rgba(197,160,89,0.06)] px-4 py-3 text-sm text-[var(--text-primary)]" role="status">
            {{ $flash }}
        </div>
    @endif

    @php
        $meta = $ga4Bundle['meta'] ?? [];
        $sum = $ga4Bundle['summary'] ?? [];
        $kpiDeepLink = auth()->user()?->hasModuleAccess(\App\ModuleAccess::MARKETING)
            ? route('modules.marketing')
            : route('settings.integrations');
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="mom-body-text text-[var(--text-secondary)]">{{ __('Analytics monitoring layer — GA4 Data API with selectable windows (7 / 28 / 90 days). KPIs include engagement and acquisition depth.') }}</p>
            @if (! empty($meta['date_range_label']))
                <p class="mom-micro mt-2 text-[var(--text-muted)]">{{ $meta['date_range_label'] }}</p>
            @endif
        </div>
        <label class="flex flex-col gap-1">
            <span class="mom-micro">{{ __('Report window') }}</span>
            <select
                wire:model.live="rangePreset"
                class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]"
            >
                <option value="7d">{{ __('Last 7 days') }}</option>
                <option value="28d">{{ __('Last 28 days') }}</option>
                <option value="90d">{{ __('Last 90 days') }}</option>
            </select>
        </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ $kpiDeepLink }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
            <p class="mom-micro">{{ __('Active users') }}</p>
            <p class="mom-metric mt-2">{{ number_format((int) ($sum['users'] ?? 0)) }}</p>
            <p class="mom-subtext mt-1">{{ data_get($meta, 'date_range_label', __('Window')) }}</p>
        </a>
        <a href="{{ $kpiDeepLink }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
            <p class="mom-micro">{{ __('New users') }}</p>
            <p class="mom-metric mt-2">{{ number_format((int) ($sum['new_users'] ?? 0)) }}</p>
            <p class="mom-subtext mt-1">{{ __('Acquisition') }}</p>
        </a>
        <a href="{{ $kpiDeepLink }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
            <p class="mom-micro">{{ __('Sessions') }}</p>
            <p class="mom-metric mt-2">{{ number_format((int) ($sum['sessions'] ?? 0)) }}</p>
            <p class="mom-subtext mt-1">{{ __('Traffic depth') }}</p>
        </a>
        <a href="{{ $kpiDeepLink }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
            <p class="mom-micro">{{ __('Engaged sessions') }}</p>
            <p class="mom-metric mt-2">{{ number_format((int) ($sum['engaged_sessions'] ?? 0)) }}</p>
            <p class="mom-subtext mt-1">{{ __('GA4 engagedSessions') }}</p>
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ $kpiDeepLink }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
            <p class="mom-micro">{{ __('Engagement rate') }}</p>
            <p class="mom-metric mt-2">
                @if (($sum['engagement_rate'] ?? null) !== null)
                    {{ number_format((float) $sum['engagement_rate'], 2) }}%
                @else
                    —
                @endif
            </p>
            <p class="mom-subtext mt-1">{{ __('Share of engaged sessions') }}</p>
        </a>
        <a href="{{ $kpiDeepLink }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
            <p class="mom-micro">{{ __('Avg. session duration') }}</p>
            <p class="mom-metric mt-2">
                @if (($sum['avg_session_duration_sec'] ?? null) !== null)
                    {{ number_format((float) $sum['avg_session_duration_sec'], 1) }}s
                @else
                    —
                @endif
            </p>
            <p class="mom-subtext mt-1">{{ __('Site-wide mean') }}</p>
        </a>
        <a href="{{ $kpiDeepLink }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
            <p class="mom-micro">{{ __('Conversions') }}</p>
            <p class="mom-metric mt-2">{{ number_format((int) ($sum['conversions'] ?? 0)) }}</p>
            <p class="mom-subtext mt-1">{{ __('Attributed events') }}</p>
        </a>
        <a href="{{ $kpiDeepLink }}" class="mom-card mom-card-interactive block px-5 py-4 no-underline">
            <p class="mom-micro">{{ __('Conversion rate') }}</p>
            <p class="mom-metric mt-2">
                @if (($sum['conversion_rate'] ?? null) !== null)
                    {{ number_format((float) $sum['conversion_rate'], 2) }}%
                @else
                    —
                @endif
            </p>
            <p class="mom-subtext mt-1">{{ __('Conversions / sessions') }}</p>
        </a>
    </div>

    <div class="flex flex-wrap items-center gap-4">
        <a href="{{ $ga4DashboardUrl }}" target="_blank" rel="noopener noreferrer" class="mom-subtext inline-flex items-center gap-1 text-mom-gold hover:underline">
            {{ __('Open GA4 dashboard') }}
            <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
        </a>
        @if ($ga4Bundle['fetched_at'] ?? null)
            <span class="mom-micro">{{ __('Cached snapshot: :t', ['t' => $ga4Bundle['fetched_at']]) }}</span>
        @endif
        <button
            type="button"
            wire:click="refreshData"
            class="ml-auto rounded-lg border border-[var(--border-panel-soft)] px-3 py-1.5 text-sm text-[var(--text-muted)] hover:border-[rgba(197,160,89,0.25)] hover:text-[var(--text-primary)]"
        >
            {{ __('Refresh data') }}
        </button>
    </div>
    @if ($ga4Bundle['error'] ?? null)
        <p class="text-sm text-[var(--danger)]">{{ $ga4Bundle['error'] }}</p>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <a href="{{ \App\Support\AdminMetricLinks::marketingIntelligence('attribution') }}" id="ga4-channels" class="mom-card mom-card-interactive block overflow-hidden p-0 no-underline">
            <h3 class="border-b border-[color:var(--border-tabstrip-divider)] px-4 py-3 text-sm font-semibold">{{ __('Channel grouping') }}</h3>
            <table class="min-w-full text-sm">
                <thead class="bg-[rgba(255,255,255,0.02)] text-left mom-micro">
                    <tr><th class="px-4 py-2">{{ __('Channel') }}</th><th class="px-4 py-2">{{ __('Sessions') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($ga4Bundle['channels'] ?? [] as $row)
                        <tr class="border-t border-[color:var(--border-tabstrip-divider)]">
                            <td class="px-4 py-2">{{ $row['channel'] }}</td>
                            <td class="px-4 py-2">{{ number_format((int) $row['sessions']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-[var(--text-muted)]">{{ __('No API rows yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </a>
        <a href="{{ \App\Support\AdminMetricLinks::marketingIntelligence('attribution') }}" class="mom-card mom-card-interactive block overflow-hidden p-0 no-underline">
            <h3 class="border-b border-[color:var(--border-tabstrip-divider)] px-4 py-3 text-sm font-semibold">{{ __('Traffic sources') }}</h3>
            <table class="min-w-full text-sm">
                <thead class="bg-[rgba(255,255,255,0.02)] text-left mom-micro">
                    <tr><th class="px-4 py-2">{{ __('Source') }}</th><th class="px-4 py-2">{{ __('Sessions') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($ga4Bundle['sources'] ?? [] as $row)
                        <tr class="border-t border-[color:var(--border-tabstrip-divider)]">
                            <td class="px-4 py-2">{{ $row['source'] }}</td>
                            <td class="px-4 py-2">{{ number_format((int) $row['sessions']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-[var(--text-muted)]">{{ __('No API rows yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <a href="{{ \App\Support\AdminMetricLinks::growthCenter('ga4') }}" class="mom-card mom-card-interactive block overflow-hidden p-0 no-underline">
            <h3 class="border-b border-[color:var(--border-tabstrip-divider)] px-4 py-3 text-sm font-semibold">{{ __('Devices') }}</h3>
            <table class="min-w-full text-sm">
                <thead class="bg-[rgba(255,255,255,0.02)] text-left mom-micro">
                    <tr><th class="px-4 py-2">{{ __('Category') }}</th><th class="px-4 py-2">{{ __('Sessions') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($ga4Bundle['devices'] ?? [] as $row)
                        <tr class="border-t border-[color:var(--border-tabstrip-divider)]">
                            <td class="px-4 py-2">{{ $row['device'] }}</td>
                            <td class="px-4 py-2">{{ number_format((int) $row['sessions']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-[var(--text-muted)]">{{ __('No API rows yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </a>
        <a href="{{ \App\Support\AdminMetricLinks::growthCenter('geo') }}" class="mom-card mom-card-interactive block overflow-hidden p-0 no-underline">
            <h3 class="border-b border-[color:var(--border-tabstrip-divider)] px-4 py-3 text-sm font-semibold">{{ __('Countries (active users)') }}</h3>
            <table class="min-w-full text-sm">
                <thead class="bg-[rgba(255,255,255,0.02)] text-left mom-micro">
                    <tr><th class="px-4 py-2">{{ __('Country') }}</th><th class="px-4 py-2">{{ __('Users') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($ga4Bundle['countries'] ?? [] as $row)
                        <tr class="border-t border-[color:var(--border-tabstrip-divider)]">
                            <td class="px-4 py-2">{{ $row['country'] }}</td>
                            <td class="px-4 py-2">{{ number_format((int) $row['users']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-[var(--text-muted)]">{{ __('No API rows yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <a href="{{ \App\Support\AdminMetricLinks::siteArchitectPages() }}" class="mom-card mom-card-interactive block overflow-hidden p-0 no-underline">
            <h3 class="border-b border-[color:var(--border-tabstrip-divider)] px-4 py-3 text-sm font-semibold">{{ __('Top pages') }}</h3>
            <table class="min-w-full text-sm">
                <thead class="bg-[rgba(255,255,255,0.02)] text-left mom-micro">
                    <tr><th class="px-4 py-2">{{ __('Path') }}</th><th class="px-4 py-2">{{ __('Views') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($ga4Bundle['pages'] ?? [] as $row)
                        <tr class="border-t border-[color:var(--border-tabstrip-divider)]">
                            <td class="max-w-xs truncate px-4 py-2" title="{{ $row['path'] }}">{{ $row['path'] }}</td>
                            <td class="px-4 py-2">{{ number_format((int) $row['views']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-[var(--text-muted)]">{{ __('No API rows yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </a>
        <a href="{{ \App\Support\AdminMetricLinks::marketingIntelligence('conversions') }}" class="mom-card mom-card-interactive block overflow-hidden p-0 no-underline">
            <h3 class="border-b border-[color:var(--border-tabstrip-divider)] px-4 py-3 text-sm font-semibold">{{ __('Events') }}</h3>
            <table class="min-w-full text-sm">
                <thead class="bg-[rgba(255,255,255,0.02)] text-left mom-micro">
                    <tr><th class="px-4 py-2">{{ __('Event') }}</th><th class="px-4 py-2">{{ __('Count') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($ga4Bundle['events'] ?? [] as $row)
                        <tr class="border-t border-[color:var(--border-tabstrip-divider)]">
                            <td class="px-4 py-2">{{ $row['name'] }}</td>
                            <td class="px-4 py-2">{{ number_format((int) $row['count']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-[var(--text-muted)]">{{ __('No API rows yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </a>
    </div>

    <p class="mom-micro text-[var(--text-muted)]">{{ __('Measurement ID and Property ID: Settings → Integrations (Google) or Marketing settings. Service account JSON: MARKETING_GA4_CREDENTIALS_PATH or GOOGLE_APPLICATION_CREDENTIALS.') }}</p>
</div>
