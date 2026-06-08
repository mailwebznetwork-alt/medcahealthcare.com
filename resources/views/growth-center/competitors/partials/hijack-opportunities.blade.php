<section class="mom-card p-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="mom-section-title">{{ __('Hijack Opportunities') }}</h2>
            <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
                {{ __('High-intent keywords where a competitor outranks Medca. Priority 1–10 is computed from SERP gap, volume, and intent.') }}
            </p>
        </div>
        <span class="mom-micro text-mom-gold">
            {{ __('Active opportunities: :count', ['count' => $hijackOpportunities->count()]) }}
        </span>
    </div>
</section>

<section class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-2">
    <article class="mom-card p-6">
        <h3 class="mom-section-title">{{ __('Record Medca SERP Position') }}</h3>
        <p class="mom-subtext mt-2">{{ __('Our ranking vs competitors is matched by keyword text (case-insensitive).') }}</p>
        <form method="post" action="{{ route('growth-center.competitors.our-ranking.store') }}" class="mt-4 space-y-3">
            @csrf
            <label class="block">
                <span class="mom-micro mb-1 block">{{ __('Keyword') }}</span>
                <input type="text" name="keyword" value="{{ old('keyword') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Our Position') }}</span>
                    <input type="number" min="1" name="position" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Recorded Date') }}</span>
                    <input type="date" name="recorded_date" value="{{ now()->toDateString() }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                </label>
            </div>
            <button type="submit" class="mom-cta-primary mom-cta-compact">{{ __('Save Our Ranking') }}</button>
        </form>
    </article>

    <article class="mom-card p-6">
        <h3 class="mom-section-title">{{ __('Site Architect bridge') }}</h3>
        <p class="mom-subtext mt-2">
            {{ __('Gemini-generated hijack strategies are stored on the global SEO entity for Site Architect to apply (meta title, description, H1, content edits).') }}
        </p>
        @if ($seoEntity && ($hijackStrategies ?? []) !== [])
            <p class="mom-micro mt-4 text-mom-gold">{{ __(':count strategy bundle(s) ready', ['count' => count($hijackStrategies ?? [])]) }}</p>
            <a href="{{ route('site-architect.pages.index') }}" class="mom-cta-primary mt-4 inline-block mom-cta-compact no-underline">
                {{ __('Open Site Architect') }}
            </a>
        @else
            <p class="mom-body-text mt-4 text-[var(--text-muted)]">{{ __('No AI strategies yet — opportunities appear after ranking shifts and GEMINI_API_KEY is set.') }}</p>
        @endif
    </article>
</section>

<section class="mom-card mt-8 overflow-x-auto p-0">
    <h3 class="mom-section-title px-6 pt-6">{{ __('Rank gap queue') }}</h3>
    <table class="mom-table mt-4 w-full min-w-[720px] text-left text-sm">
        <thead>
            <tr class="border-b border-[color:var(--border-tabstrip-divider)] text-[var(--text-muted)]">
                <th class="px-4 py-3 font-medium">{{ __('Priority') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Keyword') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Competitor') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Intent') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Their #') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Our #') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Gap') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('AI Strategy') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($hijackOpportunities as $opportunity)
                @php
                    $strategy = ($hijackStrategies ?? [])[(string) $opportunity['competitor_keyword_id']] ?? null;
                @endphp
                <tr class="border-b border-[color:var(--border-tabstrip-divider)]">
                    <td class="px-4 py-3">
                        <span class="inline-flex min-w-[2rem] items-center justify-center rounded-full bg-[rgba(212,175,55,0.15)] px-2 py-0.5 text-xs font-semibold text-mom-gold">
                            {{ $opportunity['hijack_priority'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-[var(--text-primary)]">{{ $opportunity['keyword'] }}</td>
                    <td class="px-4 py-3">{{ $opportunity['competitor_name'] ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $opportunity['intent_type'] }}</td>
                    <td class="px-4 py-3">{{ $opportunity['competitor_position'] }}</td>
                    <td class="px-4 py-3">{{ $opportunity['our_position'] }}</td>
                    <td class="px-4 py-3">+{{ $opportunity['position_gap'] }}</td>
                    <td class="px-4 py-3">
                        @if (is_array($strategy))
                            <details class="text-[var(--text-secondary)]">
                                <summary class="cursor-pointer text-mom-gold">{{ __('View') }}</summary>
                                <div class="mt-2 space-y-1 text-xs">
                                    @if (! empty($strategy['meta_title']))
                                        <p><span class="text-[var(--text-muted)]">{{ __('Title') }}:</span> {{ $strategy['meta_title'] }}</p>
                                    @endif
                                    @if (! empty($strategy['meta_description']))
                                        <p><span class="text-[var(--text-muted)]">{{ __('Description') }}:</span> {{ $strategy['meta_description'] }}</p>
                                    @endif
                                    @if (! empty($strategy['h1_suggestion']))
                                        <p><span class="text-[var(--text-muted)]">{{ __('H1') }}:</span> {{ $strategy['h1_suggestion'] }}</p>
                                    @endif
                                </div>
                            </details>
                        @else
                            <span class="text-[var(--text-muted)]">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center text-[var(--text-muted)]">
                        {{ __('No hijack opportunities yet. Add competitor + Medca rankings for the same high-intent keyword.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
