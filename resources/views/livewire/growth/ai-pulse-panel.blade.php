<div class="space-y-6">
    @if ($flash)
        <div
            class="rounded-mom-chrome border px-4 py-3 text-sm {{ $flashType === 'success' ? 'border-[rgba(197,160,89,0.25)] bg-[rgba(197,160,89,0.06)] text-[var(--text-primary)]' : 'border-[var(--danger)]/30 bg-[rgba(226,92,92,0.08)] text-[var(--danger)]' }}"
            role="status"
        >
            {{ $flash }}
        </div>
    @endif

    @if (! empty($snapshot['scan_in_progress']))
        <div class="rounded-mom-chrome border border-[rgba(197,160,89,0.2)] bg-[rgba(197,160,89,0.04)] px-4 py-3 text-sm text-[var(--text-secondary)]">
            {{ __('Snapshot loading — click “Refresh snapshot” if this stays stuck.') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.45)] p-4">
        <p class="mom-micro text-[var(--text-secondary)]">
            {{ __('Last scan:') }} <span class="text-[var(--text-primary)]">{{ $snapshot['scanned_at'] ?? '—' }}</span>
        </p>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="refreshSnapshot" class="mom-cta-ghost !px-3 !py-2 !text-[11px]">{{ __('Refresh snapshot') }}</button>
            <button type="button" wire:click="runDeepScan" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Run deep scan') }}</button>
        </div>
    </div>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="mom-card px-5 py-4">
            <p class="mom-micro">{{ __('Speed') }}</p>
            <p class="mom-metric mt-2">{{ (int) ($snapshot['scores']['speed'] ?? 0) }}<span class="text-lg text-[var(--text-muted)]">/100</span></p>
            @php($sd = $snapshot['speed_detail'] ?? [])
            <p class="mom-subtext mt-1">
                @if (($sd['source'] ?? '') === 'pagespeed_insights')
                    {{ __('Live mobile PageSpeed Insights (GOOGLE_PAGESPEED_API_KEY).') }}
                @else
                    {{ __('Baseline AI_PULSE_SPEED_BASELINE — add GOOGLE_PAGESPEED_API_KEY + AI_PULSE_PAGESPEED_URL for Lighthouse.') }}
                @endif
            </p>
        </article>
        <article class="mom-card px-5 py-4">
            <p class="mom-micro">{{ __('On-page SEO score') }}</p>
            <p class="mom-metric mt-2">{{ (int) ($snapshot['scores']['rankmath'] ?? 0) }}<span class="text-lg text-[var(--text-muted)]">/100</span></p>
            <p class="mom-subtext mt-1">{{ __('Meta, headings — averaged across pages & blogs.') }}</p>
        </article>
        <article class="mom-card px-5 py-4">
            <p class="mom-micro">{{ __('AEO / structured data') }}</p>
            <p class="mom-metric mt-2">{{ (int) ($snapshot['scores']['aio'] ?? 0) }}<span class="text-lg text-[var(--text-muted)]">/100</span></p>
            <p class="mom-subtext mt-1">{{ __('FAQ schema, AEO Q&A — averaged across pages & blogs.') }}</p>
        </article>
        <article class="mom-card px-5 py-4">
            <p class="mom-micro">{{ __('Brand authority') }}</p>
            <p class="mom-metric mt-2">{{ (int) ($snapshot['scores']['brand_authority'] ?? 0) }}<span class="text-lg text-[var(--text-muted)]">/100</span></p>
            <p class="mom-subtext mt-1">{{ __('Heuristic + optional Gemini (GEMINI_API_KEY).') }}</p>
        </article>
    </section>

    @php($sources = $snapshot['free_tier_sources'] ?? [])
    @if ($sources !== [])
        <section class="mom-card p-5">
            <h3 class="mom-section-title">{{ __('Integrations') }}</h3>
            <ul class="mom-body-text mt-3 list-disc space-y-1 pl-5 text-[var(--text-secondary)]">
                @if (! empty($sources['gemini']))
                    <li>{{ __('Gemini:') }} {{ data_get($sources, 'gemini.model') }} — {{ data_get($sources, 'gemini.source') }}</li>
                @endif
                @if (! empty($sources['pagespeed']))
                    <li>{{ __('PageSpeed:') }} {{ data_get($sources, 'pagespeed.source') }}</li>
                @endif
            </ul>
        </section>
    @endif

    <section class="mom-card p-5">
        <h3 class="mom-section-title">{{ __('Content totals') }}</h3>
        <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
            {{ __('Pages: :p · Blogs: :b · Blocks: :k', ['p' => (int) data_get($snapshot, 'totals.pages', 0), 'b' => (int) data_get($snapshot, 'totals.blogs', 0), 'k' => (int) data_get($snapshot, 'totals.blocks', 0)]) }}
        </p>
    </section>

    <section class="mom-card p-5">
        <h3 class="mom-section-title">{{ __('Recommendations') }}</h3>
        <ul class="mom-body-text mt-4 list-disc space-y-2 pl-5 text-[var(--text-secondary)]">
            @foreach (($snapshot['recommendations'] ?? []) as $note)
                <li>{{ $note }}</li>
            @endforeach
        </ul>
    </section>

    @php($pulse = $snapshot['pdf_pulse'] ?? [])
    <section class="mom-card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="mom-section-title">{{ __('AI Pulse brief (PDF §19.6)') }}</h3>
                <p class="mom-micro mt-1 text-[var(--text-muted)]">{{ __('Business health · Predictive · Conversion · GEO/AEO visibility') }}</p>
            </div>
            @if (! empty($pulse['source']))
                <span class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ $pulse['source'] }}</span>
            @endif
        </div>
        @if (! empty($pulse['lead_counts_30d']) && is_array($pulse['lead_counts_30d']))
            <p class="mom-micro mt-3 text-[var(--text-secondary)]">
                {{ __('Inquiries (30d) by source:') }}
                @foreach ($pulse['lead_counts_30d'] as $src => $cnt)
                    <span class="ml-1 font-mono text-[var(--text-primary)]">{{ $src }}={{ $cnt }}</span>@if (! $loop->last),@endif
                @endforeach
            </p>
        @endif
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-4">
                <p class="mom-micro text-mom-gold">{{ __('1) Business health') }}</p>
                <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ data_get($pulse, 'business_health') ?: '—' }}</p>
            </div>
            <div class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-4">
                <p class="mom-micro text-mom-gold">{{ __('2) Predictive') }}</p>
                <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ data_get($pulse, 'predictive_insights') ?: '—' }}</p>
            </div>
            <div class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-4">
                <p class="mom-micro text-mom-gold">{{ __('3) Conversion / marketing') }}</p>
                <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ data_get($pulse, 'conversion_insights') ?: '—' }}</p>
            </div>
            <div class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-nested)] p-4">
                <p class="mom-micro text-mom-gold">{{ __('4) Visibility — GEO / AEO') }}</p>
                <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ data_get($pulse, 'visibility_geo_aeo') ?: '—' }}</p>
            </div>
        </div>
    </section>

    <section class="mom-card overflow-hidden p-0">
        <h3 class="border-b border-[var(--border-panel-soft)] px-4 py-3 mom-section-title">{{ __('Broken / risky links') }}</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Scope') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Title') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('URL') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Reason') }}</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                    @forelse (($snapshot['broken_links'] ?? []) as $row)
                        <tr>
                            <td class="px-4 py-3 uppercase text-mom-gold">{{ $row['scope'] }}</td>
                            <td class="px-4 py-3 text-[var(--text-primary)]">{{ $row['title'] }}</td>
                            <td class="max-w-[12rem] truncate px-4 py-3 font-mono text-xs text-[var(--danger)]" title="{{ $row['url'] }}">{{ $row['url'] === '' ? '[empty]' : $row['url'] }}</td>
                            <td class="px-4 py-3 text-xs">{{ $row['reason'] }}</td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    wire:click="fixLink(@js($row['scope']), @js($row['id']), @js($row['url']))"
                                    wire:confirm="{{ __('Replace this URL with a suggested internal path?') }}"
                                    class="mom-cta-ghost !px-2 !py-1 !text-[10px]"
                                >
                                    {{ __('Fix with AI') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No issues found in scanned HTML.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
