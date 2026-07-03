@php
    $discovery = $seoDiscoveryStatus ?? [];
    $bootstrap = $seoBootstrapResult ?? null;
@endphp

<article class="mom-card mt-6 border border-[rgba(34,197,94,0.2)] p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h3 class="mom-section-title">{{ __('Phase A — Search & AI discovery setup') }}</h3>
            <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('One-click bootstrap: IndexNow key, AI crawl, profile links, GSC sitemap ping, Bing sitemap ping.') }}</p>
        </div>
        <form method="post" action="{{ route('growth-center.seo.bootstrap') }}">
            @csrf
            <button type="submit" class="mom-cta-primary mom-cta-compact">{{ __('Run bootstrap now') }}</button>
        </form>
    </div>

    @if (is_array($bootstrap))
        <ul class="mom-body-text mt-4 list-inside list-disc text-[var(--text-secondary)]">
            @foreach ($bootstrap['messages'] ?? [] as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif

    <dl class="mt-4 grid gap-3 text-sm md:grid-cols-2">
        <div><dt class="mom-micro text-[var(--text-secondary)]">{{ __('APP_URL') }}</dt><dd class="text-[var(--text-primary)]">{{ $discovery['app_url'] ?? config('app.url') }}</dd></div>
        <div><dt class="mom-micro text-[var(--text-secondary)]">{{ __('Sitemap') }}</dt><dd><a href="{{ $discovery['sitemap_url'] ?? url('/sitemap.xml') }}" class="text-[var(--success)] underline" target="_blank" rel="noopener">{{ $discovery['sitemap_url'] ?? url('/sitemap.xml') }}</a></dd></div>
        <div><dt class="mom-micro text-[var(--text-secondary)]">{{ __('AI discovery') }}</dt><dd>{{ ($discovery['ai_discovery_enabled'] ?? false) ? __('Enabled') : __('Disabled') }}</dd></div>
        <div><dt class="mom-micro text-[var(--text-secondary)]">{{ __('AI crawl (robots)') }}</dt><dd>{{ ($discovery['ai_crawl_enabled'] ?? false) ? __('Allowed') : __('Blocked') }}</dd></div>
        <div><dt class="mom-micro text-[var(--text-secondary)]">{{ __('Google verification') }}</dt><dd>{{ ($discovery['google_verification_set'] ?? false) ? __('Set') : __('Not set') }}</dd></div>
        <div><dt class="mom-micro text-[var(--text-secondary)]">{{ __('Bing verification') }}</dt><dd>{{ ($discovery['bing_verification_set'] ?? false) ? __('Set') : __('Not set — paste token in technical form') }}</dd></div>
        <div><dt class="mom-micro text-[var(--text-secondary)]">{{ __('GSC OAuth') }}</dt><dd>{{ ($discovery['gsc_configured'] ?? false) ? __('Connected') : __('Connect under Growth Center → GSC') }}</dd></div>
        <div class="md:col-span-2"><dt class="mom-micro text-[var(--text-secondary)]">{{ __('IndexNow key URL') }}</dt><dd class="break-all">@if (filled($discovery['indexnow_key_url'] ?? null))<a href="{{ $discovery['indexnow_key_url'] }}" class="text-[var(--success)] underline" target="_blank" rel="noopener">{{ $discovery['indexnow_key_url'] }}</a>@else<span class="text-amber-300">{{ __('Run bootstrap to generate') }}</span>@endif</dd></div>
    </dl>
</article>

<article class="mom-card mt-6 border border-[rgba(255,255,255,0.06)] p-5">
    <h3 class="mom-section-title">{{ __('Phase B — Redirect manager') }}</h3>
    <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('Database-driven 301/302 redirects for any public path. Slug redirects from Site Architect still work for CMS pages.') }}</p>

    <form method="post" action="{{ route('growth-center.seo.redirects.store') }}" class="mt-4 grid gap-3 md:grid-cols-4">
        @csrf
        <label class="block md:col-span-1">
            <span class="mom-micro mb-1 block">{{ __('From path') }}</span>
            <input type="text" name="from_path" placeholder="/old-path" required class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
        </label>
        <label class="block md:col-span-2">
            <span class="mom-micro mb-1 block">{{ __('To URL') }}</span>
            <input type="text" name="to_url" placeholder="/new-path or https://..." required class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
        </label>
        <label class="block">
            <span class="mom-micro mb-1 block">{{ __('Status') }}</span>
            <select name="status_code" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm">
                <option value="301">301</option>
                <option value="302">302</option>
            </select>
        </label>
        <div class="md:col-span-4">
            <button type="submit" class="mom-cta-primary mom-cta-compact">{{ __('Save redirect') }}</button>
        </div>
    </form>

    @if (($urlRedirects ?? collect())->isNotEmpty())
        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="mom-micro text-[var(--text-secondary)]"><tr><th class="py-2">{{ __('From') }}</th><th>{{ __('To') }}</th><th>{{ __('Code') }}</th><th>{{ __('Hits') }}</th><th></th></tr></thead>
                <tbody>
                    @foreach ($urlRedirects as $redirect)
                        <tr class="border-t border-[rgba(255,255,255,0.06)]">
                            <td class="py-2 font-mono text-xs">{{ $redirect->from_path }}</td>
                            <td class="max-w-xs truncate font-mono text-xs">{{ $redirect->to_url }}</td>
                            <td>{{ $redirect->status_code }}</td>
                            <td>{{ number_format($redirect->hit_count) }}</td>
                            <td>
                                <form method="post" action="{{ route('growth-center.seo.redirects.destroy', $redirect) }}" onsubmit="return confirm(@json(__('Delete this redirect?')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-300 hover:underline">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</article>

<article class="mom-card mt-6 border border-[rgba(255,255,255,0.06)] p-5">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="mom-section-title">{{ __('Phase B — Broken link scanner') }}</h3>
            <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('Weekly automated scan + on-demand queue. Checks internal links on key public pages.') }}</p>
        </div>
        <form method="post" action="{{ route('growth-center.seo.scan-broken-links') }}">
            @csrf
            <button type="submit" class="mom-cta-secondary mom-cta-compact">{{ __('Run scan now') }}</button>
        </form>
    </div>

    @foreach (($brokenLinkReports ?? collect()) as $report)
        <div class="mt-4 rounded-mom-chrome border border-[rgba(255,255,255,0.06)] p-4">
            <p class="mom-micro">{{ $report->completed_at?->format('Y-m-d H:i') ?? $report->created_at->format('Y-m-d H:i') }} — {{ strtoupper($report->status) }}</p>
            <p class="mom-body-text mt-1 text-[var(--text-secondary)]">{{ __('Pages: :p, links: :l, broken: :b', ['p' => $report->pages_scanned, 'l' => $report->links_checked, 'b' => $report->broken_count]) }}</p>
            @if (is_array($report->findings) && count($report->findings) > 0)
                <ul class="mt-2 max-h-40 overflow-y-auto text-xs text-rose-200 custom-scrollbar">
                    @foreach (array_slice($report->findings, 0, 20) as $finding)
                        <li class="py-1"><span class="text-[var(--text-secondary)]">{{ $finding['source'] ?? '' }}</span> → {{ $finding['target'] ?? '' }} ({{ $finding['status'] ?? '?' }})</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach
</article>
