<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a
                href="{{ route('system.source-of-truth') }}"
                class="mom-subtext inline-flex items-center gap-1.5 text-[var(--text-muted)] transition hover:text-[var(--text-primary)]"
            >
                <span aria-hidden="true">←</span>
                {{ __('Back to Source of Truth') }}
            </a>
            <h2 class="mom-section-title mt-3">{{ $listLabel }}</h2>
            <p class="mom-subtext mt-1">
                {{ __(':count record(s)', ['count' => number_format($listRows->total())]) }}
            </p>
        </div>
    </div>

    <article class="mom-card overflow-hidden p-0">
        @if ($listRows->isEmpty())
            <p class="mom-body-text p-6 text-[var(--text-muted)]">{{ __('No records found for this metric.') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            @foreach ($listColumns as $column)
                                <th class="px-4 py-3 font-medium">{{ $column['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                        @foreach ($listRows as $row)
                            <tr>
                                @foreach ($listColumns as $column)
                                    <td class="px-4 py-3 align-top">
                                        @switch($column['key'])
                                            @case('registry_key')
                                                <span class="font-mono text-xs text-[var(--text-primary)]">{{ $row->registry_key }}</span>
                                                @break
                                            @case('entity_type')
                                                {{ $row->entity_type }}
                                                @break
                                            @case('source')
                                                {{ $row->source ?? '—' }}
                                                @break
                                            @case('page')
                                                @if ($row->page)
                                                    <a
                                                        href="{{ route('site-architect.pages.index', ['edit' => $row->page->id]) }}"
                                                        class="text-mom-gold hover:underline"
                                                    >{{ $row->page->title ?: $row->page->slug }}</a>
                                                @elseif ($row->page_id)
                                                    <span class="text-[var(--danger)]">#{{ $row->page_id }}</span>
                                                @else
                                                    —
                                                @endif
                                                @break
                                            @case('public_path')
                                                <span class="font-mono text-xs">{{ $row->public_path ?: '—' }}</span>
                                                @break
                                            @case('title')
                                                <span class="font-medium text-[var(--text-primary)]">{{ $row->title ?: '—' }}</span>
                                                @break
                                            @case('slug')
                                                <span class="font-mono text-xs">{{ $row->slug }}</span>
                                                @break
                                            @case('lifecycle_state')
                                                {{ str_replace('_', ' ', (string) $row->lifecycle_state) }}
                                                @break
                                            @case('page_source')
                                                {{ $row->page_source ?: '—' }}
                                                @break
                                            @case('open')
                                                <a
                                                    href="{{ route('site-architect.pages.index', ['edit' => $row->id]) }}"
                                                    class="text-mom-gold hover:underline"
                                                >{{ __('Open page') }}</a>
                                                @break
                                            @case('natural_key')
                                                <span class="font-mono text-xs text-[var(--text-primary)]">{{ $row->natural_key }}</span>
                                                @break
                                            @case('deleted_at')
                                                {{ $row->deleted_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}
                                                @break
                                            @case('reason')
                                                {{ $row->reason ?: '—' }}
                                                @break
                                            @default
                                                —
                                        @endswitch
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($listRows->hasPages())
                <div class="mom-backend-hairline-t px-4 py-3">
                    {{ $listRows->links() }}
                </div>
            @endif
        @endif
    </article>
</div>
