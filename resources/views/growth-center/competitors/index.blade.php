<x-app-layout
    :page-title="__('Growth Center')"
    :welcome-line="__('Competitor War Room workspace.')"
>
    @if (session('status'))
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ session('status') }}</p>
    @endif

    <section class="mom-card p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="mom-section-title">{{ __('Competitors') }}</h2>
                <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
                    {{ __('Track competitors, keywords, and conversion movement in one place.') }}
                </p>
            </div>
            <span class="mom-micro text-mom-gold">{{ __('Total: :count', ['count' => $competitors->total()]) }}</span>
        </div>
    </section>

    <section class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-4">
        <article class="mom-card p-5">
            <p class="mom-micro">{{ __('Total Competitors') }}</p>
            <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($summary['total_competitors'] ?? 0)) }}</p>
        </article>
        <article class="mom-card p-5">
            <p class="mom-micro">{{ __('Active Competitors') }}</p>
            <p class="mom-metric mt-2 leading-none">{{ number_format((int) ($summary['active_competitors'] ?? 0)) }}</p>
        </article>
        <article class="mom-card p-5">
            <p class="mom-micro">{{ __('Best Performer') }}</p>
            <p class="mom-body-text mt-2 text-[var(--text-primary)]">{{ data_get($summary, 'best_competitor.name', '—') }}</p>
        </article>
        <article class="mom-card p-5">
            <p class="mom-micro">{{ __('Worst Performer') }}</p>
            <p class="mom-body-text mt-2 text-[var(--text-primary)]">{{ data_get($summary, 'worst_competitor.name', '—') }}</p>
        </article>
    </section>

    <section class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <article class="mom-card p-6">
            <h3 class="mom-section-title">{{ __('Add Competitor') }}</h3>
            <form method="post" action="{{ route('growth-center.competitors.store') }}" class="mt-4 space-y-3">
                @csrf
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Name') }}</span>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Website') }}</span>
                    <input type="url" name="website" value="{{ old('website') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="is_intercept_target" value="1" class="rounded border-[rgba(255,255,255,0.12)] bg-transparent text-[var(--success)]">
                    <span class="mom-micro">{{ __('Intercept target') }}</span>
                </label>
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Save') }}</button>
            </form>
        </article>

        <article class="mom-card p-6">
            <h3 class="mom-section-title">{{ __('Bulk Add Competitors') }}</h3>
            <p class="mom-subtext mt-2">{{ __('One line per competitor: Name|Website|Intercept(yes/no)') }}</p>
            <form method="post" action="{{ route('growth-center.competitors.bulk-store') }}" class="mt-4 space-y-3">
                @csrf
                <textarea name="bulk_competitors" rows="7" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" placeholder="Aster Labs|https://aster.example|yes&#10;Care Plus|https://careplus.example|no">{{ old('bulk_competitors') }}</textarea>
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Bulk Save') }}</button>
            </form>
        </article>
    </section>

    <section class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <article class="mom-card p-6">
            <h3 class="mom-section-title">{{ __('Add Keyword Intelligence') }}</h3>
            <form method="post" action="{{ route('growth-center.competitors.keywords.store') }}" class="mt-4 space-y-3">
                @csrf
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Competitor') }}</span>
                    <select name="competitor_id" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                        @foreach ($allCompetitors as $competitor)
                            <option value="{{ $competitor->id }}">{{ $competitor->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Keyword') }}</span>
                    <input type="text" name="keyword" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Intent Type') }}</span>
                    <select name="intent_type" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                        <option value="brand">brand</option>
                        <option value="service">service</option>
                        <option value="local">local</option>
                    </select>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('Search Volume') }}</span>
                        <input type="number" min="0" name="search_volume" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                    </label>
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('Difficulty') }}</span>
                        <input type="number" min="0" max="100" name="difficulty" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                    </label>
                </div>
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Save Keyword') }}</button>
            </form>
        </article>

        <article class="mom-card p-6">
            <h3 class="mom-section-title">{{ __('Add Tracking Data') }}</h3>
            <form method="post" action="{{ route('growth-center.competitors.tracking.store') }}" class="mt-4 space-y-3">
                @csrf
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Keyword') }}</span>
                    <select name="competitor_keyword_id" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                        @foreach ($allKeywords as $keyword)
                            <option value="{{ $keyword->id }}">{{ $keyword->competitor?->name ?? '—' }} - {{ $keyword->keyword }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('Clicks') }}</span>
                        <input type="number" min="0" name="clicks" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                    </label>
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('Impressions') }}</span>
                        <input type="number" min="0" name="impressions" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                    </label>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('Position') }}</span>
                        <input type="number" min="1" name="position" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                    </label>
                    <label class="block">
                        <span class="mom-micro mb-1 block">{{ __('Recorded Date') }}</span>
                        <input type="date" name="recorded_date" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                    </label>
                </div>
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Save Tracking') }}</button>
            </form>
        </article>

        <article class="mom-card p-6">
            <h3 class="mom-section-title">{{ __('Add Lead Attribution') }}</h3>
            <form method="post" action="{{ route('growth-center.competitors.leads.store') }}" class="mt-4 space-y-3">
                @csrf
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Keyword (optional)') }}</span>
                    <select name="competitor_keyword_id" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                        <option value="">{{ __('General lead (no keyword)') }}</option>
                        @foreach ($allKeywords as $keyword)
                            <option value="{{ $keyword->id }}">{{ $keyword->competitor?->name ?? '—' }} - {{ $keyword->keyword }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Source') }}</span>
                    <select name="source" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                        <option value="google_ads">google_ads</option>
                        <option value="seo">seo</option>
                        <option value="meta">meta</option>
                        <option value="direct">direct</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Status') }}</span>
                    <select name="status" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                        <option value="new">new</option>
                        <option value="contacted">contacted</option>
                        <option value="converted">converted</option>
                        <option value="lost">lost</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mom-micro mb-1 block">{{ __('Details') }}</span>
                    <textarea name="details" rows="3" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]"></textarea>
                </label>
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Save Lead') }}</button>
            </form>
        </article>
    </section>

    <section class="mom-card mt-8 p-6">
        <h3 class="mom-section-title">{{ __('Compare Competitors') }}</h3>
        <form method="post" action="{{ route('growth-center.competitors.compare') }}" class="mt-4 space-y-3">
            @csrf
            <label class="block">
                <span class="mom-micro mb-1 block">{{ __('Select 2 to 10 competitors') }}</span>
                <select name="competitor_ids[]" multiple size="6" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
                    @foreach ($competitors as $competitor)
                        <option value="{{ $competitor->id }}" @selected(in_array($competitor->id, $selectedCompetitorIds ?? [], true))>
                            {{ $competitor->name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Run Comparison') }}</button>
        </form>
    </section>

    @if ($comparison)
        <section class="mom-card mt-8 p-6">
            <h3 class="mom-section-title">{{ __('Comparison Result') }}</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[44rem] text-left text-[13px]">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('Competitor') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('Keywords') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('Clicks') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('Conversions') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('Conversion Rate') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                        @foreach ($comparison as $row)
                            <tr>
                                <td class="px-4 py-3 text-[var(--text-primary)]">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((int) $row['total_keywords']) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((int) $row['clicks']) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((int) $row['conversions']) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) $row['conversion_rate'], 2) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($keywordOverlap)
        <section class="mom-card mt-8 p-6">
            <h3 class="mom-section-title">{{ __('Keyword Overlap') }}</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[32rem] text-left text-[13px]">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('Keyword') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('Competitor Count') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                        @foreach ($keywordOverlap as $overlapRow)
                            <tr>
                                <td class="px-4 py-3 text-[var(--text-primary)]">{{ $overlapRow->keyword }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((int) $overlapRow->competitor_count) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="mom-card mt-8 p-6">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Competitor') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Website') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ __('Keywords') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ __('Leads') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Intercept') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                    @forelse ($competitors as $competitor)
                        <tr>
                            <td class="px-4 py-3 text-[var(--text-primary)]">{{ $competitor->name }}</td>
                            <td class="px-4 py-3">{{ $competitor->website ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((int) $competitor->keywords_count) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((int) $competitor->leads_count) }}</td>
                            <td class="px-4 py-3">{{ $competitor->is_active ? __('Active') : __('Inactive') }}</td>
                            <td class="px-4 py-3">{{ $competitor->is_intercept_target ? __('Yes') : __('No') }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="post" action="{{ route('growth-center.competitors.destroy', $competitor) }}" class="inline">
                                    @csrf
                                    @method('delete')
                                    <button
                                        type="submit"
                                        class="mom-cta-ghost !px-3 !py-2 !text-[11px] text-[var(--danger)]"
                                        onclick="return confirm('Remove this competitor?')"
                                    >
                                        {{ __('Remove') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No competitors available.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $competitors->links() }}
        </div>
    </section>
</x-app-layout>
