@php
    $catalogKind = $catalogKind ?? 'service';
    $scores = $optimizationScores ?? [];
    $recommendations = $seoRecommendations ?? [];
    $locationPageCount = $locationPageCount ?? 0;
    $labels = [
        'category' => __('Category master — optimization hub'),
        'sub_service' => __('Sub-service master — optimization hub'),
        'service' => __('Services master — optimization hub'),
    ];
    $scoreItems = [
        'seo' => __('SEO'),
        'aeo' => __('AEO'),
        'geo' => __('GEO'),
        'schema' => __('Schema'),
        'content' => __('Content'),
        'local' => __('Local SEO'),
        'image' => __('Image SEO'),
        'ai_discovery' => __('AI Discovery'),
    ];
@endphp

@if ($service->exists)
    <section class="mom-card mb-6 p-6" aria-label="{{ __('Optimization scores') }}">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="mom-section-title">{{ $labels[$catalogKind] ?? $labels['service'] }}</h3>
                <p class="mom-subtext mt-1 max-w-2xl text-sm">
                    @if ($catalogKind === 'service')
                        {{ __('This service is the single source of truth. Saving syncs SEO, AEO, GEO, schema, the detail page, and :count location page(s).', ['count' => $locationPageCount]) }}
                    @else
                        {{ __('Saving syncs SEO, AEO, GEO, schema, FAQs, media, and the linked discovery page.') }}
                    @endif
                </p>
            </div>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            @foreach ($scoreItems as $key => $label)
                @php $value = (int) ($scores[$key] ?? 0); @endphp
                <div class="rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.45)] p-3">
                    <p class="mom-micro text-[var(--text-muted)]">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $value >= 70 ? 'text-[var(--success)]' : ($value >= 40 ? 'text-mom-gold' : 'text-[var(--danger)]') }}">
                        {{ $value }}<span class="text-sm font-normal text-[var(--text-muted)]">/100</span>
                    </p>
                </div>
            @endforeach
        </div>

        @if ($recommendations !== [])
            <div class="mt-6">
                <h4 class="text-sm font-semibold text-[var(--text-primary)]">{{ __('Recommendations') }}</h4>
                <ul class="mt-2 space-y-2 text-sm">
                    @foreach (array_slice($recommendations, 0, 8) as $rec)
                        @if (is_array($rec) && filled($rec['message'] ?? null))
                            <li class="flex gap-2 rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2">
                                <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ ($rec['priority'] ?? '') === 'high' ? 'bg-[rgba(226,92,92,0.15)] text-[var(--danger)]' : 'bg-[rgba(197,160,89,0.12)] text-mom-gold' }}">
                                    {{ $rec['area'] ?? 'tip' }}
                                </span>
                                <span class="text-[var(--text-secondary)]">{{ $rec['message'] }}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif
    </section>
@endif
