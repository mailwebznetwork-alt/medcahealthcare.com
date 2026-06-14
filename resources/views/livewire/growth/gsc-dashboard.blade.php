<div class="space-y-6">
    @if ($flash)
        <div class="rounded-mom-chrome border border-[rgba(197,160,89,0.25)] bg-[rgba(197,160,89,0.06)] px-4 py-3 text-sm" role="status">{{ $flash }}</div>
    @endif

    @if (session('gsc_connected'))
        <div class="rounded-mom-chrome border border-[rgba(197,160,89,0.25)] bg-[rgba(197,160,89,0.06)] px-4 py-3 text-sm" role="status">{{ __('Google Search Console connected.') }}</div>
    @endif

    @if (session('gsc_disconnected'))
        <div class="rounded-mom-chrome border border-[rgba(255,255,255,0.08)] bg-[var(--bg-card-nested)] px-4 py-3 text-sm" role="status">{{ __('Google Search Console disconnected.') }}</div>
    @endif

    @if ($errors->has('gsc'))
        <p class="mom-body-text text-[var(--danger)]">{{ $errors->first('gsc') }}</p>
    @endif

    <p class="mom-body-text text-[var(--text-secondary)]">
        {{ __('Google Search Console Search Analytics. Connect with OAuth or set MEDCA_GSC_* tokens in .env.') }}
    </p>

    <div class="flex flex-wrap items-end gap-4">
        @if ($canManageOAuth)
            @if ($oauthConnected)
                <form action="{{ route('growth-center.gsc.oauth.disconnect') }}" method="POST">
                    @csrf
                    <button type="submit" class="mom-cta-ghost mom-cta-compact">{{ __('Disconnect GSC') }}</button>
                </form>
            @elseif ($oauthConnectable)
                <a href="{{ route('growth-center.gsc.oauth.connect') }}" class="mom-cta-primary mom-cta-compact">{{ __('Connect Google Search Console') }}</a>
            @else
                <p class="mom-subtext">{{ __('Set MEDCA_GSC_CLIENT_ID and MEDCA_GSC_CLIENT_SECRET to enable OAuth connect.') }}</p>
            @endif
        @endif
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
        @if (! empty($connection['auth_mode']))
            <p class="mom-micro text-[var(--text-muted)]">{{ __('Auth: :mode', ['mode' => $connection['auth_mode']]) }}</p>
        @endif
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
