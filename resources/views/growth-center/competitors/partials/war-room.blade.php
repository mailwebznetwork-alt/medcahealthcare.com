@php
    $rollup = $warRoomRollup ?? [];
    $localityLabel = trim(implode(', ', array_filter([
        isset($businessProfile) ? ($businessProfile->city ?? null) : null,
        isset($businessProfile) ? ($businessProfile->region ?? null) : null,
    ]))) ?: __('Growth coverage footprint');
@endphp

<section class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <a href="{{ route('growth-center.competitors.index', ['tab' => 'competitors']) }}" class="mom-card flex min-h-[190px] flex-col p-5 no-underline">
        <p class="mom-micro text-mom-gold">{{ __('Silent Hijack Monitor') }}</p>
        <p class="mom-metric mt-2 leading-none tabular-nums">
            {{ number_format((int) ($rollup['interceptOn'] ?? 0)) }}
            <span class="text-lg font-semibold text-[var(--text-muted)]"> / {{ number_format((int) ($rollup['active'] ?? 0)) }}</span>
            <span class="mom-subtext block font-normal">{{ __('intercept targets · active') }}</span>
        </p>
        <p class="mom-body-text mt-2 flex-1 text-[var(--text-secondary)]">{{ __('Intercept routing vs active competitors. Toggle intercept target on each competitor record.') }}</p>
        <span class="mom-cta-primary mt-4 !inline-flex !w-fit mom-cta-compact">{{ __('Open roster') }}</span>
    </a>

    <a href="{{ route('growth-center.competitors.index', ['tab' => 'competitors']) }}#growth-keyword-intelligence" class="mom-card flex min-h-[190px] flex-col p-5 no-underline">
        <p class="mom-micro text-mom-gold">{{ __('Keyword intelligence') }}</p>
        <p class="mom-metric mt-2 leading-none tabular-nums">
            {{ number_format((int) ($rollup['matrixRows'] ?? 0)) }}
            <span class="mom-subtext font-normal">{{ __('tracking rows') }}</span>
            ·
            <span class="text-[var(--accent-gold)]">{{ number_format((int) ($rollup['keywordGaps'] ?? 0)) }}</span>
            <span class="mom-subtext font-normal">{{ __('keywords') }}</span>
        </p>
        <p class="mom-body-text mt-2 flex-1 text-[var(--text-secondary)]">{{ __('Tracked SERP rows plus keyword workbook entries across competitors.') }}</p>
        <p class="mom-micro mt-2 text-[var(--text-muted)]">{{ __('Keywords = intelligence entries · Rows = tracking snapshots') }}</p>
    </a>

    <a href="{{ route('growth-center.competitors.index', ['tab' => 'seo']) }}#growth-geo-coverage" class="mom-card flex min-h-[190px] flex-col p-5 no-underline">
        <p class="mom-micro text-mom-gold">{{ __('GEO footprint') }}</p>
        <p class="mom-body-text mt-2 text-[var(--text-primary)]">{{ $localityLabel }}</p>
        <p class="mom-metric mt-2 leading-none tabular-nums">
            {{ number_format((int) ($rollup['geoRefRules'] ?? 0)) }}
            <span class="mom-subtext font-normal">{{ __('GEO PIN rows') }}</span>
        </p>
        <p class="mom-body-text mt-2 flex-1 text-[var(--text-secondary)]">{{ __('Growth Center pin coverage aligned with local discovery.') }}</p>
        <span class="mom-cta-primary mt-4 !inline-flex !w-fit mom-cta-compact">{{ __('SEO & entity') }}</span>
    </a>

    <a href="{{ route('growth-center.competitors.index', ['tab' => 'competitors']) }}#growth-lead-attribution" class="mom-card flex min-h-[190px] flex-col p-5 no-underline">
        <p class="mom-micro text-mom-gold">{{ __('Attribution mirror') }}</p>
        @if (((int) ($rollup['attributionLeads'] ?? 0)) > 0)
            <p class="mom-metric mt-2 leading-none tabular-nums">
                {{ number_format((int) $rollup['attributionLeads']) }}
                <span class="mom-subtext font-normal">{{ __('attributed leads') }}</span>
            </p>
        @else
            <p class="mom-body-text mt-2 text-[var(--text-muted)]">{{ __('Log leads against keywords to mirror conversion pressure.') }}</p>
        @endif
        <p class="mom-body-text mt-2 flex-1 text-[var(--text-secondary)]">
            {{ __('Total competitors in workbook: :n', ['n' => number_format((int) ($rollup['total'] ?? 0))]) }}
        </p>
    </a>
</section>

<section class="mb-8">
    @php $backlinkSummary = $backlinkSummary ?? ['gap_count' => 0, 'top_gaps' => [], 'site_backlink_domains' => 0, 'competitor_backlink_domains' => 0]; @endphp
    <div class="mom-card p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="mom-section-title">{{ __('Backlink gap intelligence') }}</h2>
                <p class="mom-body-text mt-2 max-w-2xl text-[var(--text-secondary)]">
                    {{ __('Domains where competitors earn citations but Medca does not — sourced from backlink monitor scans and local directory probes.') }}
                </p>
            </div>
            <div class="flex gap-6 text-center">
                <div>
                    <p class="mom-micro">{{ __('Gap domains') }}</p>
                    <p class="mom-metric mt-1">{{ number_format((int) ($backlinkSummary['gap_count'] ?? 0)) }}</p>
                </div>
                <div>
                    <p class="mom-micro">{{ __('Competitor refs') }}</p>
                    <p class="mom-metric mt-1">{{ number_format((int) ($backlinkSummary['competitor_backlink_domains'] ?? 0)) }}</p>
                </div>
                <div>
                    <p class="mom-micro">{{ __('Medca refs') }}</p>
                    <p class="mom-metric mt-1">{{ number_format((int) ($backlinkSummary['site_backlink_domains'] ?? 0)) }}</p>
                </div>
            </div>
        </div>
        <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[36rem] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3">{{ __('Referring domain') }}</th>
                        <th class="px-4 py-3">{{ __('Competitors linked') }}</th>
                        <th class="px-4 py-3">{{ __('Names') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)]">
                    @forelse ($backlinkSummary['top_gaps'] ?? [] as $gap)
                        <tr>
                            <td class="px-4 py-3 font-medium text-[var(--text-primary)]">{{ $gap['domain'] ?? '—' }}</td>
                            <td class="px-4 py-3">{{ (int) ($gap['competitor_count'] ?? 0) }}</td>
                            <td class="px-4 py-3 text-[var(--text-secondary)]">{{ implode(', ', $gap['competitors'] ?? []) ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No backlink gaps detected yet — run a competitor scan from Growth settings or wait for the scheduled job.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="mom-card p-6">
    <h2 class="mom-section-title">{{ __('Intercept queue') }}</h2>
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <a href="#intercept-queue-form" class="mom-card block p-5 no-underline">
            <p class="mom-micro">{{ __('Pending Intercepts') }}</p>
            <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($warRoomDashboard['pending_intercepts'] ?? 0)) }}</p>
        </a>
        <a href="#intercept-queue-form" class="mom-card block p-5 no-underline">
            <p class="mom-micro">{{ __('In Progress') }}</p>
            <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($warRoomDashboard['in_progress_intercepts'] ?? 0)) }}</p>
        </a>
        <a href="#intercept-queue-form" class="mom-card block p-5 no-underline">
            <p class="mom-micro">{{ __('Completed Intercepts') }}</p>
            <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($warRoomDashboard['completed_intercepts'] ?? 0)) }}</p>
        </a>
    </div>

    <form id="intercept-queue-form" method="post" action="{{ route('growth-center.war-room.intercept.store') }}" class="mt-6 grid grid-cols-1 gap-3 xl:grid-cols-5">
        @csrf
        <input type="text" name="keyword" placeholder="{{ __('Keyword') }}" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
        <input type="text" name="gap_type" placeholder="{{ __('Gap type') }}" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
        <select name="competitor_id" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
            <option value="">{{ __('Competitor (optional)') }}</option>
            @foreach ($allCompetitors as $competitor)
                <option value="{{ $competitor->id }}">{{ $competitor->name }}</option>
            @endforeach
        </select>
        <select name="priority" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
            <option value="high">High</option>
            <option value="medium" selected>Medium</option>
            <option value="low">Low</option>
        </select>
        <input type="text" name="action" placeholder="{{ __('Action') }}" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
        <button type="submit" class="mom-cta-primary mom-cta-compact">{{ __('Create Intercept') }}</button>
    </form>
</section>

<section class="mom-card mt-8 p-6">
    <h3 class="mom-section-title">{{ __('Intercepts') }}</h3>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[44rem] text-left text-[13px]">
            <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('Keyword') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Competitor') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Gap Type') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Action') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Priority') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                @forelse ($intercepts as $intercept)
                    <tr>
                        <td class="px-4 py-3 text-[var(--text-primary)]">{{ $intercept->keyword }}</td>
                        <td class="px-4 py-3">{{ $intercept->competitor?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $intercept->gap_type }}</td>
                        <td class="px-4 py-3">{{ $intercept->action }}</td>
                        <td class="px-4 py-3">{{ $intercept->priority }}</td>
                        <td class="px-4 py-3">{{ $intercept->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No intercepts found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
