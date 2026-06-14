<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="mom-body-text text-[var(--text-secondary)]">
            {{ __('Catalog content quality, entity graph gaps, and medical review backlog.') }}
        </p>
        <button type="button" wire:click="refresh" class="mom-cta-ghost mom-cta-compact">{{ __('Refresh') }}</button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="mom-card px-5 py-4">
            <p class="mom-micro">{{ __('Programmatic SEO average score') }}</p>
            <p class="mom-metric mt-2 tabular-nums">{{ number_format((float) ($seoQuality['average'] ?? 0), 1) }}</p>
        </div>
        <div class="mom-card px-5 py-4">
            <p class="mom-micro">{{ __('Low quality services (&lt;60)') }}</p>
            <p class="mom-metric mt-2 tabular-nums">{{ number_format((int) ($seoQuality['low_quality_count'] ?? 0)) }}</p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            'thin_services' => __('Thin services'),
            'missing_quick_answer' => __('Missing quick answer'),
            'missing_ai_summary' => __('Missing AI summary'),
            'pending_medical_review' => __('Pending medical review'),
            'pages_missing_schema_json' => __('Pages missing schema'),
            'thin_indexable_locations' => __('Thin indexable locations'),
        ] as $key => $label)
            <div class="mom-card px-5 py-4">
                <p class="mom-micro">{{ $label }}</p>
                <p class="mom-metric mt-2 tabular-nums">{{ number_format((int) ($health[$key] ?? 0)) }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($graphSummary as $key => $value)
            <div class="mom-card px-5 py-4">
                <p class="mom-micro">{{ str_replace('_', ' ', ucfirst($key)) }}</p>
                <p class="mom-metric mt-2 tabular-nums">{{ number_format((int) $value) }}</p>
            </div>
        @endforeach
    </div>

    @if (! empty($health['recommendations']))
        <div class="mom-card p-5">
            <h3 class="mom-section-title text-base">{{ __('Recommendations') }}</h3>
            <ul class="mom-body-text mt-3 list-inside list-disc space-y-2 text-[var(--text-secondary)]">
                @foreach ($health['recommendations'] as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($lowScoreSamples->isNotEmpty())
        <div class="mom-card overflow-hidden p-0">
            <div class="mom-backend-hairline-b px-5 py-4">
                <h3 class="mom-section-title text-base">{{ __('Low SEO score services') }}</h3>
            </div>
            <div class="mom-table overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-[13px]">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="px-4 py-3">{{ __('Code') }}</th>
                            <th class="px-4 py-3">{{ __('Score') }}</th>
                            <th class="px-4 py-3">{{ __('Gaps') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                        @foreach ($lowScoreSamples as $sample)
                            <tr>
                                <td class="px-4 py-3 font-mono">{{ $sample['code'] }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ (int) $sample['score'] }}</td>
                                <td class="px-4 py-3 text-xs">{{ implode(', ', $sample['gaps'] ?? []) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($thinSamples->isNotEmpty())
        <div class="mom-card overflow-hidden p-0">
            <div class="mom-backend-hairline-b px-5 py-4">
                <h3 class="mom-section-title text-base">{{ __('Thin content samples') }}</h3>
            </div>
            <div class="mom-table overflow-x-auto">
                <table class="w-full min-w-[480px] text-left text-[13px]">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="px-4 py-3">{{ __('Code') }}</th>
                            <th class="px-4 py-3">{{ __('Title') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                        @foreach ($thinSamples as $service)
                            <tr>
                                <td class="px-4 py-3 font-mono">{{ $service->service_code }}</td>
                                <td class="px-4 py-3">{{ $service->title }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
