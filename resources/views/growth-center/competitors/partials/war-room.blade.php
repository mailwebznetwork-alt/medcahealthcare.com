@php
    $rollup = $warRoomRollup ?? [];
    $localityLabel = trim(implode(', ', array_filter([
        isset($businessProfile) ? ($businessProfile->city ?? null) : null,
        isset($businessProfile) ? ($businessProfile->region ?? null) : null,
    ]))) ?: __('Growth coverage footprint');
@endphp

<section class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="mom-card flex min-h-[190px] flex-col p-5">
        <p class="mom-micro text-mom-gold">{{ __('Silent Hijack Monitor') }}</p>
        <p class="mom-metric mt-2 leading-none tabular-nums">
            {{ number_format((int) ($rollup['interceptOn'] ?? 0)) }}
            <span class="text-lg font-semibold text-[var(--text-muted)]"> / {{ number_format((int) ($rollup['active'] ?? 0)) }}</span>
            <span class="mom-subtext block font-normal">{{ __('intercept targets · active') }}</span>
        </p>
        <p class="mom-body-text mt-2 flex-1 text-[var(--text-secondary)]">{{ __('Intercept routing vs active competitors. Toggle intercept target on each competitor record.') }}</p>
        <a href="{{ route('growth-center.competitors.index', ['tab' => 'competitors']) }}" class="mom-cta-primary mt-4 !inline-flex !w-fit !px-3 !py-2 !text-[11px]">{{ __('Open roster') }}</a>
    </article>

    <article class="mom-card flex min-h-[190px] flex-col p-5">
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
    </article>

    <article class="mom-card flex min-h-[190px] flex-col p-5">
        <p class="mom-micro text-mom-gold">{{ __('GEO footprint') }}</p>
        <p class="mom-body-text mt-2 text-[var(--text-primary)]">{{ $localityLabel }}</p>
        <p class="mom-metric mt-2 leading-none tabular-nums">
            {{ number_format((int) ($rollup['geoRefRules'] ?? 0)) }}
            <span class="mom-subtext font-normal">{{ __('GEO PIN rows') }}</span>
        </p>
        <p class="mom-body-text mt-2 flex-1 text-[var(--text-secondary)]">{{ __('Growth Center pin coverage aligned with local discovery.') }}</p>
        <a href="{{ route('growth-center.competitors.index', ['tab' => 'seo']) }}" class="mom-cta-primary mt-4 !inline-flex !w-fit !px-3 !py-2 !text-[11px]">{{ __('SEO & entity') }}</a>
    </article>

    <article class="mom-card flex min-h-[190px] flex-col p-5">
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
    </article>
</section>

<section class="mom-card p-6">
    <h2 class="mom-section-title">{{ __('Intercept queue') }}</h2>
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <article class="mom-card p-5">
            <p class="mom-micro">{{ __('Pending Intercepts') }}</p>
            <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($warRoomDashboard['pending_intercepts'] ?? 0)) }}</p>
        </article>
        <article class="mom-card p-5">
            <p class="mom-micro">{{ __('In Progress') }}</p>
            <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($warRoomDashboard['in_progress_intercepts'] ?? 0)) }}</p>
        </article>
        <article class="mom-card p-5">
            <p class="mom-micro">{{ __('Completed Intercepts') }}</p>
            <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($warRoomDashboard['completed_intercepts'] ?? 0)) }}</p>
        </article>
    </div>

    <form method="post" action="{{ route('growth-center.war-room.intercept.store') }}" class="mt-6 grid grid-cols-1 gap-3 xl:grid-cols-5">
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
        <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Create Intercept') }}</button>
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
            <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
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
