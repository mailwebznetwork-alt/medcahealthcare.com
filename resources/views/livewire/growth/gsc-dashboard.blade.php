<div class="space-y-6">
    @if ($flash)
        <div class="rounded-mom-chrome border border-[rgba(197,160,89,0.25)] bg-[rgba(197,160,89,0.06)] px-4 py-3 text-sm" role="status">{{ $flash }}</div>
    @endif

    <p class="mom-body-text text-[var(--text-secondary)]">
        {{ __('Google Search Console Search Analytics — set MEDCA_GSC_ACCESS_TOKEN and MEDCA_GSC_SITE_URL in .env.') }}
    </p>

    <div class="flex flex-wrap items-end gap-4">
        <button type="button" wire:click="testConnection" class="mom-cta-ghost mom-cta-compact">{{ __('Test connection') }}</button>
        <label class="flex flex-col gap-1">
            <span class="mom-micro">{{ __('Days') }}</span>
            <select wire:model="days" class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                <option value="7">7</option>
                <option value="28">28</option>
                <option value="90">90</option>
            </select>
        </label>
        <button type="button" wire:click="loadAnalytics" class="mom-cta-primary mom-cta-compact">{{ __('Load queries') }}</button>
    </div>

    @if ($connection['error'])
        <p class="mom-body-text text-[var(--danger)]">{{ $connection['error'] }}</p>
    @elseif (! empty($connection['sites']))
        <p class="mom-micro text-[var(--text-muted)]">{{ __('Sites: :sites', ['sites' => implode(', ', $connection['sites'])]) }}</p>
    @endif

    @if ($queryError)
        <p class="mom-body-text text-[var(--danger)]">{{ $queryError }}</p>
    @endif

    @if ($rows !== [])
        <div class="mom-card overflow-hidden p-0">
            <div class="mom-table overflow-x-auto max-h-96 overflow-y-auto custom-scrollbar">
                <table class="w-full min-w-[640px] text-left text-[13px]">
                    <thead class="sticky top-0 bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="px-4 py-3">{{ __('Query') }}</th>
                            <th class="px-4 py-3">{{ __('Page') }}</th>
                            <th class="px-4 py-3">{{ __('Clicks') }}</th>
                            <th class="px-4 py-3">{{ __('Impressions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                        @foreach (array_slice($rows, 0, 50) as $row)
                            <tr>
                                <td class="px-4 py-3">{{ $row['keys'][0] ?? '—' }}</td>
                                <td class="px-4 py-3 text-[12px]">{{ $row['keys'][1] ?? '—' }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ (int) ($row['clicks'] ?? 0) }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ (int) ($row['impressions'] ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
