@php
    $metrics = $report['metrics'] ?? [];
    $lastSync = $report['last_sync'] ?? [];
    $governance = $report['governance'] ?? [];
    $registry = $report['registry'] ?? [];
    $cascade = $report['cascade'] ?? [];
    $health = $report['health'] ?? [];
    $orphanRows = $report['orphan_rows'] ?? [];
    $issues = $health['issues'] ?? [];
    $checks = $health['checks'] ?? [];

    $metricCards = [
        ['key' => 'pages', 'label' => __('CMS pages'), 'value' => $metrics['pages'] ?? 0],
        ['key' => 'location_mappings', 'label' => __('Location pages'), 'value' => ($metrics['location_mappings'] ?? 0).' / '.($metrics['expected_location_pages'] ?? 0)],
        ['key' => 'registry_rows', 'label' => __('Registry entries'), 'value' => $metrics['registry_rows'] ?? 0],
        ['key' => 'distinct_registry_pages', 'label' => __('Pages in registry'), 'value' => $metrics['distinct_registry_pages'] ?? 0],
        ['key' => 'generated', 'label' => __('Generated entries'), 'value' => $metrics['generated'] ?? 0],
        ['key' => 'manual', 'label' => __('Manual entries'), 'value' => $metrics['manual'] ?? 0],
        ['key' => 'planned', 'label' => __('Planned entries'), 'value' => $metrics['planned'] ?? 0],
        ['key' => 'orphan_registry', 'label' => __('Orphan registry'), 'value' => $metrics['orphan_registry'] ?? 0, 'warn' => ($metrics['orphan_registry'] ?? 0) > 0],
        ['key' => 'tombstones', 'label' => __('Tombstones'), 'value' => $metrics['tombstones'] ?? 0],
        ['key' => 'protected_pages', 'label' => __('Protected pages'), 'value' => $metrics['protected_pages'] ?? 0],
        ['key' => 'admin_overrides', 'label' => __('Admin overrides'), 'value' => $metrics['admin_overrides'] ?? 0],
    ];
@endphp

<div class="space-y-8">
    @if ($flash)
        <div
            @class([
                'rounded-mom-chrome border px-4 py-3 text-sm text-[var(--text-primary)]',
                'border-[rgba(197,160,89,0.25)] bg-[rgba(197,160,89,0.06)]' => $flashType === 'success',
                'border-[var(--border-panel-soft)] bg-[rgba(28,22,18,0.45)]' => $flashType !== 'success',
            ])
            role="status"
        >
            {{ $flash }}
        </div>
    @endif

    @if (! empty($cascade['last_failure']['message']))
        <div class="rounded-mom-chrome border border-[var(--danger)]/40 bg-[rgba(220,38,38,0.08)] px-4 py-3 text-sm text-[var(--text-primary)]" role="alert">
            <p class="font-semibold text-[var(--danger)]">{{ __('Catalog cascade failed') }}</p>
            <p class="mom-subtext mt-1">{{ $cascade['last_failure']['message'] }}</p>
            <p class="mom-micro mt-2 text-[var(--text-muted)]">
                {{ __('Scope') }}: {{ $cascade['last_failure']['scope'] ?? '—' }}
                · {{ $cascade['last_failure']['at'] ?? '' }}
            </p>
        </div>
    @endif

    @if ($this->isListMode())
        @include('livewire.system.partials.source-of-truth-list', [
            'listRows' => $listRows,
            'listLabel' => $listLabel,
            'listColumns' => $listColumns,
            'listKey' => $listKey,
        ])
    @else
    <div class="flex flex-wrap items-center justify-between gap-4">
        <article class="mom-card flex-1 min-w-[16rem] px-5 py-4">
            <p class="mom-micro">{{ __('Last registry sync') }}</p>
            <p class="mom-body-text mt-2 font-semibold text-[var(--text-primary)]">
                {{ $lastSync['label'] ?? __('Unknown') }}
            </p>
            @if (! empty($lastSync['at']))
                <p class="mom-subtext mt-1">{{ $lastSync['at'] }}</p>
            @endif
            @if (! empty($lastSync['counts']['synced']))
                <p class="mom-subtext mt-1">
                    {{ __('Registry entries synced') }}: {{ number_format((int) $lastSync['counts']['synced']) }}
                </p>
            @endif
        </article>

        <div class="flex flex-wrap gap-3">
            <button
                type="button"
                wire:click="syncRegistry"
                wire:confirm="{{ __('Sync the universal page registry from the database? This updates registry rows to match current pages and catalog entities.') }}"
                class="mom-cta-primary mom-cta-compact"
            >
                {{ __('Sync registry') }}
            </button>
            <button
                type="button"
                wire:click="purgeOrphans"
                wire:confirm="{{ __('Remove orphan registry rows whose database entities no longer exist? This cannot recreate missing pages.') }}"
                class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-4 py-2 text-sm font-semibold text-[var(--text-primary)] transition hover:border-[rgba(197,160,89,0.35)]"
            >
                {{ __('Purge orphans') }}
            </button>
            <button
                type="button"
                wire:click="refreshReport"
                class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-4 py-2 text-sm text-[var(--text-muted)] transition hover:text-[var(--text-primary)]"
            >
                {{ __('Refresh') }}
            </button>
        </div>
    </div>

    <section class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($metricCards as $card)
            <x-admin.metric-card
                :label="$card['label']"
                :value="number_format((int) $card['value'])"
                :href="\App\Support\AdminMetricLinks::sourceOfTruthMetric($card['key'])"
                :warn="! empty($card['warn'])"
                class="px-4 py-3"
            />
        @endforeach
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <article id="source-of-truth-governance" class="mom-card scroll-mt-32 p-6">
            <h2 class="mom-section-title">{{ __('Governance') }}</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-[color:var(--border-tabstrip-divider)] text-[var(--text-muted)]">
                            <th class="py-2 pr-4 font-medium">{{ __('Component') }}</th>
                            <th class="py-2 pr-4 font-medium">{{ __('Status') }}</th>
                            <th class="py-2 font-medium">{{ __('Detail') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($governance as $row)
                            <tr class="border-b border-[color:var(--border-tabstrip-divider)]">
                                <td class="py-3 pr-4 font-medium text-[var(--text-primary)]">{{ $row['component'] }}</td>
                                <td class="py-3 pr-4">
                                    <span @class([
                                        'font-semibold',
                                        'text-[var(--success)]' => $row['enabled'] ?? false,
                                        'text-[var(--danger)]' => ! ($row['enabled'] ?? false),
                                    ])>{{ $row['status'] }}</span>
                                </td>
                                <td class="py-3 text-[var(--text-secondary)]">{{ $row['detail'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>

        <article class="mom-card p-6">
            <h2 class="mom-section-title">{{ __('Registry services') }}</h2>
            <ul class="mt-4 space-y-3">
                <li class="flex items-center justify-between rounded-lg border border-[var(--border-panel-soft)] px-4 py-3">
                    <span class="text-sm text-[var(--text-primary)]">{{ __('UniversalPageRegistry') }}</span>
                    <span class="text-sm font-semibold text-[var(--success)]">{{ $registry['universal_page_registry'] ?? '—' }}</span>
                </li>
                <li class="flex items-center justify-between rounded-lg border border-[var(--border-panel-soft)] px-4 py-3">
                    <span class="text-sm text-[var(--text-primary)]">{{ $registry['sync_command'] ?? 'medca:sync-page-registry' }}</span>
                    <span @class([
                        'text-sm font-semibold',
                        'text-[var(--success)]' => $registry['sync_available'] ?? false,
                        'text-[var(--danger)]' => ! ($registry['sync_available'] ?? false),
                    ])>{{ ($registry['sync_available'] ?? false) ? __('Available') : __('Missing') }}</span>
                </li>
                <li class="flex items-center justify-between rounded-lg border border-[var(--border-panel-soft)] px-4 py-3">
                    <span class="text-sm text-[var(--text-primary)]">{{ $registry['purge_command'] ?? 'medca:purge-registry-orphans' }}</span>
                    <span @class([
                        'text-sm font-semibold',
                        'text-[var(--success)]' => $registry['purge_available'] ?? false,
                        'text-[var(--danger)]' => ! ($registry['purge_available'] ?? false),
                    ])>{{ ($registry['purge_available'] ?? false) ? __('Available') : __('Missing') }}</span>
                </li>
            </ul>
        </article>
    </div>

    <article class="mom-card p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="mom-section-title">{{ __('Health') }}</h2>
            <span @class([
                'rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide',
                'bg-[rgba(34,197,94,0.12)] text-[var(--success)]' => $health['aligned'] ?? false,
                'bg-[rgba(239,68,68,0.12)] text-[var(--danger)]' => ! ($health['aligned'] ?? true),
            ])>
                {{ ($health['aligned'] ?? false) ? __('Aligned') : __('Attention needed') }}
            </span>
        </div>

        <ul class="mt-4 space-y-3">
            @foreach ($checks as $check)
                <li class="flex flex-col gap-1 rounded-lg border border-[var(--border-panel-soft)] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-[var(--text-primary)]">{{ $check['label'] }}</p>
                        <p class="mom-subtext mt-1">{{ $check['detail'] }}</p>
                    </div>
                    <span @class([
                        'text-sm font-semibold',
                        'text-[var(--success)]' => $check['ok'] ?? false,
                        'text-[var(--danger)]' => ! ($check['ok'] ?? false),
                    ])>{{ $check['value'] }}</span>
                </li>
            @endforeach
        </ul>
    </article>

    @if ($orphanRows !== [] || $issues !== [])
        <div class="grid gap-6 xl:grid-cols-2">
            <article id="source-of-truth-orphans" class="mom-card scroll-mt-32 p-6">
                <h2 class="mom-section-title">{{ __('Orphan registry rows') }}</h2>
                @if ($orphanRows === [])
                    <p class="mom-subtext mt-4">{{ __('No orphan registry rows detected.') }}</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-[color:var(--border-tabstrip-divider)] text-[var(--text-muted)]">
                                    <th class="py-2 pr-4 font-medium">{{ __('Registry key') }}</th>
                                    <th class="py-2 pr-4 font-medium">{{ __('Type') }}</th>
                                    <th class="py-2 font-medium">{{ __('Page ID') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orphanRows as $row)
                                    <tr class="border-b border-[color:var(--border-tabstrip-divider)]">
                                        <td class="py-2 pr-4 font-mono text-xs text-[var(--text-primary)]">{{ $row['registry_key'] }}</td>
                                        <td class="py-2 pr-4 text-[var(--text-secondary)]">{{ $row['entity_type'] }}</td>
                                        <td class="py-2 text-[var(--text-secondary)]">{{ $row['page_id'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>

            <article class="mom-card p-6">
                <h2 class="mom-section-title">{{ __('Detected issues') }}</h2>
                @if ($issues === [])
                    <p class="mom-subtext mt-4">{{ __('No registry drift issues detected.') }}</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-[color:var(--border-tabstrip-divider)] text-[var(--text-muted)]">
                                    <th class="py-2 pr-4 font-medium">{{ __('Type') }}</th>
                                    <th class="py-2 pr-4 font-medium">{{ __('Registry key') }}</th>
                                    <th class="py-2 font-medium">{{ __('Detail') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($issues as $issue)
                                    <tr class="border-b border-[color:var(--border-tabstrip-divider)]">
                                        <td class="py-2 pr-4 text-[var(--text-secondary)]">{{ $issue['type'] }}</td>
                                        <td class="py-2 pr-4 font-mono text-xs text-[var(--text-primary)]">{{ $issue['registry_key'] }}</td>
                                        <td class="py-2 text-[var(--text-secondary)]">{{ $issue['detail'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>
        </div>
    @endif
    @endif
</div>
