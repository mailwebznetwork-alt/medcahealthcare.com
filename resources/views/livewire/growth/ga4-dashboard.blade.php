<div class="space-y-6">
    @if ($flash)
        <div class="rounded-mom-chrome border border-[rgba(197,160,89,0.25)] bg-[rgba(197,160,89,0.06)] px-4 py-3 text-sm text-[var(--text-primary)]" role="status">
            {{ $flash }}
        </div>
    @endif

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
        <div class="mom-card overflow-hidden p-0">
            <h3 class="border-b border-[var(--border-panel-soft)] px-4 py-3 text-sm font-semibold">{{ __('Traffic sources') }}</h3>
            <table class="min-w-full text-sm">
                <thead class="bg-[rgba(255,255,255,0.02)] text-left mom-micro">
                    <tr><th class="px-4 py-2">{{ __('Source') }}</th><th class="px-4 py-2">{{ __('Sessions') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($ga4Bundle['sources'] ?? [] as $row)
                        <tr class="border-t border-[var(--border-panel-soft)]">
                            <td class="px-4 py-2">{{ $row['source'] }}</td>
                            <td class="px-4 py-2">{{ number_format((int) $row['sessions']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-[var(--text-muted)]">{{ __('No API rows yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mom-card overflow-hidden p-0">
            <h3 class="border-b border-[var(--border-panel-soft)] px-4 py-3 text-sm font-semibold">{{ __('Top pages') }}</h3>
            <table class="min-w-full text-sm">
                <thead class="bg-[rgba(255,255,255,0.02)] text-left mom-micro">
                    <tr><th class="px-4 py-2">{{ __('Path') }}</th><th class="px-4 py-2">{{ __('Views') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($ga4Bundle['pages'] ?? [] as $row)
                        <tr class="border-t border-[var(--border-panel-soft)]">
                            <td class="max-w-xs truncate px-4 py-2" title="{{ $row['path'] }}">{{ $row['path'] }}</td>
                            <td class="px-4 py-2">{{ number_format((int) $row['views']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-[var(--text-muted)]">{{ __('No API rows yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mom-card overflow-hidden p-0">
        <h3 class="border-b border-[var(--border-panel-soft)] px-4 py-3 text-sm font-semibold">{{ __('Events') }}</h3>
        <table class="min-w-full text-sm">
            <thead class="bg-[rgba(255,255,255,0.02)] text-left mom-micro">
                <tr><th class="px-4 py-2">{{ __('Event') }}</th><th class="px-4 py-2">{{ __('Count') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($ga4Bundle['events'] ?? [] as $row)
                    <tr class="border-t border-[var(--border-panel-soft)]">
                        <td class="px-4 py-2">{{ $row['name'] }}</td>
                        <td class="px-4 py-2">{{ number_format((int) $row['count']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-4 py-6 text-[var(--text-muted)]">{{ __('No API rows yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="mom-micro text-[var(--text-muted)]">{{ __('Measurement ID and Property ID: Settings → Integrations (Google) or Marketing settings.') }}</p>
</div>
