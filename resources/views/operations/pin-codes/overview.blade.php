<x-operations.workspace>
    @php
        $importResult = session('import_result');
    @endphp
    @if (is_array($importResult))
        <div class="mom-card mb-8 p-5" role="status">
            <p class="mom-section-title">{{ __('Latest import run') }}</p>
            <ul class="mom-body-text mt-3 list-inside list-disc space-y-1 text-[var(--text-secondary)]">
                <li>{{ __('Created: :n', ['n' => (int) ($importResult['created'] ?? 0)]) }}</li>
                <li>{{ __('Skipped (duplicates): :n', ['n' => (int) ($importResult['skipped'] ?? 0)]) }}</li>
                <li>{{ __('Failed rows: :n', ['n' => (int) ($importResult['failed'] ?? 0)]) }}</li>
            </ul>
            @if (! empty($importResult['errors']) && is_array($importResult['errors']))
                <div class="mom-subtext mt-4 max-h-40 overflow-y-auto rounded-mom-sm border border-[rgba(255,255,255,0.06)] bg-[rgba(0,0,0,0.15)] p-3">
                    @foreach ($importResult['errors'] as $err)
                        <p class="text-[var(--warning)]">{{ $err }}</p>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => __('Total pincodes'), 'value' => number_format($metrics['total']), 'hint' => __('All records in the directory')],
            ['label' => __('Serviceable areas'), 'value' => number_format($metrics['serviceable']), 'hint' => __('Eligible for operational coverage')],
            ['label' => __('Non-serviceable'), 'value' => number_format($metrics['non_serviceable']), 'hint' => __('Explicitly excluded from service')],
            ['label' => __('Active locations'), 'value' => number_format($metrics['active']), 'hint' => __('Records currently enabled')],
        ] as $card)
            <article class="mom-card px-5 py-4">
                <p class="mom-micro">{{ $card['label'] }}</p>
                <p class="mom-metric mt-2 leading-none">{{ $card['value'] }}</p>
                <p class="mom-subtext mt-2">{{ $card['hint'] }}</p>
            </article>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <article class="mom-card px-5 py-4">
            <p class="mom-micro">{{ __('Local SEO readiness') }}</p>
            <p class="mom-metric mt-2 leading-none">{{ number_format($seo['geo_page_ready']) }}</p>
            <p class="mom-subtext mt-2">{{ __('Locations flagged for future geo landing pages') }}</p>
            <dl class="mom-backend-hairline-t mom-subtext mt-4 space-y-2 pt-4 text-[var(--text-secondary)]">
                <div class="flex justify-between gap-4">
                    <dt>{{ __('With meta title') }}</dt>
                    <dd class="font-medium text-[var(--text-primary)]">{{ number_format($seo['with_meta_title']) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt>{{ __('With meta description') }}</dt>
                    <dd class="font-medium text-[var(--text-primary)]">{{ number_format($seo['with_meta_description']) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt>{{ __('With SEO keywords') }}</dt>
                    <dd class="font-medium text-[var(--text-primary)]">{{ number_format($seo['with_seo_keywords']) }}</dd>
                </div>
            </dl>
        </article>
        <article class="mom-card px-5 py-4">
            <p class="mom-micro">{{ __('Operational coverage') }}</p>
            <p class="mom-metric mt-2 leading-none">
                @if ($metrics['total'] > 0)
                    {{ number_format(($metrics['serviceable'] / $metrics['total']) * 100, 1) }}%
                @else
                    —
                @endif
            </p>
            <p class="mom-subtext mt-2">{{ __('Share of directory marked serviceable (planning signal, not routing logic).') }}</p>
        </article>
    </div>

    <div class="mom-card mt-12 overflow-hidden p-0">
        <div class="mom-backend-hairline-b px-5 py-4">
            <h3 class="mom-section-title text-base">{{ __('Import history') }}</h3>
            <p class="mom-subtext mt-1">{{ __('Recent CSV runs, outcomes, and status.') }}</p>
        </div>
        @if ($importLogs->isEmpty())
            <p class="mom-body-text p-6 text-[var(--text-muted)]">{{ __('No imports recorded yet.') }}</p>
        @else
            <div class="mom-table overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-[13px]">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('When') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('File') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Created') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Skipped') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Failed') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                        @foreach ($importLogs as $log)
                            <tr>
                                <td class="px-4 py-3 text-[var(--text-primary)]">{{ $log->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3">
                                    <span class="max-w-[14rem] truncate font-mono text-[12px]" title="{{ $log->original_filename }}">{{ $log->original_filename }}</span>
                                </td>
                                <td class="px-4 py-3">{{ number_format($log->rows_created) }}</td>
                                <td class="px-4 py-3">{{ number_format($log->rows_skipped) }}</td>
                                <td class="px-4 py-3">{{ number_format($log->rows_failed) }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-mom-pill border border-[rgba(255,255,255,0.06)] px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide">{{ str_replace('_', ' ', $log->status) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-operations.workspace>
