@php
    $lockedWorkbook = $lockedWorkbook ?? null;
    $workbookMeta = $lockedWorkbook ? ($workbooks[$lockedWorkbook] ?? []) : [];
    $workbookLabel = $workbookMeta['label'] ?? $lockedWorkbook;
    $approveRoutePrefix = $approveRoutePrefix ?? 'operations.bulk-import';
    $previewRoute = $previewRoute ?? route('operations.bulk-import.preview');
    $confirmRoute = $confirmRoute ?? route('operations.bulk-import.confirm');
    $cancelRoute = $cancelRoute ?? route('operations.bulk-import.cancel');
    $templateRoute = fn (string $key): string => route('operations.bulk-import.templates.download', $key);
@endphp

@if (session('import_submitted_for_approval'))
    <div class="mom-card mb-8 border border-[rgba(197,160,89,0.35)] p-5" role="status">
        <p class="mom-section-title text-mom-gold">{{ __('Submitted for checker approval') }}</p>
        <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
            {{ __('A manager, admin, or medical reviewer must approve this import before rows are committed.') }}
        </p>
    </div>
@endif

@if (session('import_rejected'))
    <div class="mom-card mb-8 border border-[rgba(220,38,38,0.25)] p-5" role="status">
        <p class="mom-section-title text-[var(--danger)]">{{ __('Import rejected') }}</p>
    </div>
@endif

@if (session('import_async_started'))
    @php $async = session('import_async_started'); @endphp
    <div class="mom-card mb-8 border border-[rgba(59,130,246,0.35)] p-5" role="status">
        <p class="mom-section-title text-sky-300">{{ __('Import running in background') }}</p>
        <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
            {{ __('Your file (:file, :n rows) is importing now. This avoids gateway timeouts on large workbooks.', [
                'file' => $async['filename'] ?? __('workbook'),
                'n' => number_format((int) ($async['rows'] ?? 0)),
            ]) }}
        </p>
        <p class="mom-body-text mt-2 text-[var(--text-secondary)]">
            {{ __('Refresh this page in 1–2 minutes and check Import history below for batch results.') }}
        </p>
    </div>
@endif

@if (session('import_result'))
    @php $r = session('import_result'); @endphp
    <div class="mom-card mb-8 border border-[rgba(34,197,94,0.25)] p-5" role="status">
        <p class="mom-section-title text-emerald-300">{{ __('Import complete') }}</p>
        <ul class="mom-body-text mt-3 list-inside list-disc space-y-1 text-[var(--text-secondary)]">
            <li>{{ __('Created: :n', ['n' => (int) ($r['created'] ?? 0)]) }}</li>
            <li>{{ __('Updated: :n', ['n' => (int) ($r['updated'] ?? 0)]) }}</li>
            <li>{{ __('Skipped: :n', ['n' => (int) ($r['skipped'] ?? 0)]) }}</li>
            <li>{{ __('Failed: :n', ['n' => (int) ($r['failed'] ?? 0)]) }}</li>
        </ul>
        @if (! empty($r['post_sync_pending']))
            <p class="mom-body-text mt-3 text-[var(--text-secondary)]">
                {{ __('CMS pages and service links are syncing in the background. Refresh in a minute if new pages are not visible yet.') }}
            </p>
        @endif
    </div>
@endif

<h2 class="mom-section-title mb-2">{{ __('Bulk import') }}</h2>
<p class="mom-subtext mb-8 max-w-3xl text-[var(--text-secondary)]">
    @if ($lockedWorkbook === 'services')
        {{ __('Upload services.xlsx to import categories, services, sub-services, and location defaults in one run. Preview validates every sheet before commit.') }}
    @elseif ($lockedWorkbook === 'pincodes')
        {{ __('Upload pincodes.xlsx to import pincodes and geo enrichment. Service–pincode mappings are created automatically after import when services exist.') }}
    @else
        {{ __('Upload a master workbook or single entity file. Preview validates rows before commit.') }}
    @endif
</p>

@if ($errors->has('file'))
    <div class="mom-card mb-8 border border-[rgba(220,38,38,0.25)] p-4" role="alert">
        <p class="mom-section-title text-base text-[var(--danger)]">{{ __('Import blocked') }}</p>
        <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ $errors->first('file') }}</p>
    </div>
@endif

@if (is_array($staging) && (($staging['mode'] ?? '') === 'workbook' || ! empty($staging['preview']['sheets'])))
    <div class="mom-card mb-8 overflow-hidden p-0">
        <div class="mom-backend-hairline-b px-5 py-4">
            <h3 class="mom-section-title text-base">{{ __('Workbook validation') }} — {{ $staging['workbook'] ?? $lockedWorkbook }}</h3>
            <p class="mom-subtext mt-1">{{ __('Total data rows: :n', ['n' => number_format((int) ($staging['total_data_rows'] ?? 0))]) }}</p>
            @if (! empty($staging['preview']['warnings']))
                <ul class="mom-subtext mt-2 space-y-1 text-[var(--warning)]">
                    @foreach ($staging['preview']['warnings'] as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="max-h-72 overflow-y-auto custom-scrollbar px-5 py-4 space-y-4">
            @foreach (($staging['preview']['sheets'] ?? []) as $sheet)
                <div>
                    <p class="text-sm font-semibold text-[var(--text-primary)]">
                        {{ $sheet['sheet_name'] ?? $sheet['sheet_key'] }}
                        <span class="text-[var(--text-muted)]">({{ $sheet['entity'] }})</span>
                    </p>
                    <p class="mom-micro mt-1">
                        {{ $sheet['total_data_rows'] ?? 0 }} {{ __('rows') }}
                        @if (! empty($sheet['import_summary']))
                            @php $s = $sheet['import_summary']; @endphp
                            · {{ __(':unique unique pincodes', ['unique' => number_format((int) ($s['unique_pincodes'] ?? 0))]) }}
                            @if (($s['duplicate_rows'] ?? 0) > 0)
                                · {{ __(':n duplicate rows collapsed (last row wins)', ['n' => number_format((int) $s['duplicate_rows'])]) }}
                            @endif
                            · {{ __('Commit forecast: :create create, :update update, :restore restore, :skip skip', [
                                'create' => number_format((int) ($s['would_create'] ?? 0)),
                                'update' => number_format((int) ($s['would_update'] ?? 0)),
                                'restore' => number_format((int) ($s['would_restore'] ?? 0)),
                                'skip' => number_format((int) ($s['would_skip'] ?? 0)),
                            ]) }}
                        @endif
                        @if (! empty($sheet['missing_columns']))
                            · {{ __('Missing template columns: :n', ['n' => count($sheet['missing_columns'])]) }}
                        @endif
                    </p>
                    <table class="mom-table mt-2 w-full min-w-[480px] text-left text-[12px]">
                        <thead class="text-[var(--text-muted)]">
                            <tr>
                                <th class="px-2 py-1">{{ __('Line') }}</th>
                                <th class="px-2 py-1">{{ __('Status') }}</th>
                                <th class="px-2 py-1">{{ __('Key') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-[var(--text-secondary)]">
                            @foreach (($sheet['rows'] ?? []) as $row)
                                <tr class="border-t border-[color:var(--border-tabstrip-divider)]">
                                    <td class="px-2 py-1 font-mono">{{ $row['line'] ?? '—' }}</td>
                                    <td class="px-2 py-1">{{ $row['status'] ?? '—' }}</td>
                                    <td class="px-2 py-1">{{ $row['key'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
        <div class="mom-backend-hairline-t flex flex-wrap items-center justify-between gap-4 px-5 py-4">
            <form method="post" action="{{ $cancelRoute }}" class="inline">@csrf<button type="submit" class="mom-cta-ghost">{{ __('Cancel staging') }}</button></form>
            <form method="post" action="{{ $confirmRoute }}" class="inline">@csrf<button type="submit" class="mom-cta-primary">{{ config('import_registry.workflow.maker_checker_enabled') ? __('Submit for approval') : __('Approve & commit import') }}</button></form>
        </div>
    </div>
@elseif (is_array($staging) && ! empty($staging['preview_rows']))
    <div class="mom-card mb-8 overflow-hidden p-0">
        <div class="mom-backend-hairline-b px-5 py-4">
            <h3 class="mom-section-title text-base">{{ __('Import preview') }} — {{ $staging['entity'] ?? '' }}</h3>
            <p class="mom-subtext mt-1">{{ __('Total data rows: :n', ['n' => number_format((int) ($staging['total_data_rows'] ?? 0))]) }}</p>
        </div>
        <div class="mom-table overflow-x-auto">
            <table class="w-full min-w-[480px] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3">{{ __('Line') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Key') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                    @foreach ($staging['preview_rows'] as $row)
                        <tr>
                            <td class="px-4 py-3 font-mono">{{ $row['line'] ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $row['status'] ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $row['key'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mom-backend-hairline-t flex flex-wrap items-center justify-between gap-4 px-5 py-4">
            <form method="post" action="{{ $cancelRoute }}" class="inline">@csrf<button type="submit" class="mom-cta-ghost">{{ __('Cancel staging') }}</button></form>
            <form method="post" action="{{ $confirmRoute }}" class="inline">@csrf<button type="submit" class="mom-cta-primary">{{ config('import_registry.workflow.maker_checker_enabled') ? __('Submit for approval') : __('Approve & commit import') }}</button></form>
        </div>
    </div>
@endif

<div class="mom-card mb-8 p-6">
    <h3 class="mom-section-title text-base">{{ __('Upload & preview') }}</h3>
  @if ($lockedWorkbook)
        <p class="mom-subtext mt-2 max-w-3xl text-[var(--text-secondary)]">
            {{ __('Workbook: :file', ['file' => $workbookLabel]) }}
        </p>
        <div class="mt-3">
            <a href="{{ $templateRoute($lockedWorkbook) }}" class="text-sm font-semibold text-mom-gold hover:underline">
                {{ __('Download :file template', ['file' => $workbookLabel]) }}
            </a>
        </div>
    @endif
    <form method="post" action="{{ $previewRoute }}" enctype="multipart/form-data" class="mt-6 space-y-4" id="bulk-import-form">
        @csrf
        @if ($lockedWorkbook)
            <input type="hidden" name="import_mode" value="workbook">
            <input type="hidden" name="workbook" value="{{ $lockedWorkbook }}">
        @else
            <div>
                <x-input-label :value="__('Import mode')" variant="mom" />
                <div class="mt-2 flex flex-wrap gap-4 text-sm text-[var(--text-secondary)]">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="import_mode" value="workbook" checked onchange="window.medcaToggleImportMode && window.medcaToggleImportMode()">
                        {{ __('Master workbook') }}
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="import_mode" value="entity" onchange="window.medcaToggleImportMode && window.medcaToggleImportMode()">
                        {{ __('Single entity file') }}
                    </label>
                </div>
            </div>
            <div id="bulk-import-workbook-panel">
                <x-input-label for="workbook" :value="__('Workbook')" variant="mom" />
                <select id="workbook" name="workbook" class="rounded-mom-chrome mt-2 block w-full max-w-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">
                    @foreach ($workbooks as $key => $meta)
                        <option value="{{ $key }}">{{ $meta['label'] ?? $key }}</option>
                    @endforeach
                </select>
                <div class="mt-3 flex flex-wrap gap-4 text-sm">
                    <a href="{{ $templateRoute('services') }}" class="font-semibold text-mom-gold hover:underline">{{ __('Download services.xlsx') }}</a>
                    <a href="{{ $templateRoute('pincodes') }}" class="font-semibold text-mom-gold hover:underline">{{ __('Download pincodes.xlsx') }}</a>
                </div>
            </div>
            <div id="bulk-import-entity-panel" class="hidden">
                <x-input-label for="entity" :value="__('Entity')" variant="mom" />
                <select id="entity" name="entity" class="rounded-mom-chrome mt-2 block w-full max-w-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">
                    @foreach ($entities as $key => $meta)
                        @if (($meta['status'] ?? '') === 'implemented')
                            <option value="{{ $key }}">{{ ucfirst(str_replace('_', ' ', $key)) }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <script>
                window.medcaToggleImportMode = function () {
                    var form = document.getElementById('bulk-import-form');
                    if (!form) return;
                    var mode = form.querySelector('input[name="import_mode"]:checked');
                    var workbook = document.getElementById('bulk-import-workbook-panel');
                    var entity = document.getElementById('bulk-import-entity-panel');
                    if (!mode || !workbook || !entity) return;
                    var isWorkbook = mode.value === 'workbook';
                    workbook.classList.toggle('hidden', !isWorkbook);
                    entity.classList.toggle('hidden', isWorkbook);
                };
            </script>
        @endif
        <div>
            <x-input-label for="file" :value="__('File (XLSX)')" variant="mom" />
            <input
                id="file"
                name="file"
                type="file"
                accept=".xls,.xlsx,.csv,.txt"
                required
                class="mt-2 block w-full text-sm text-[var(--text-secondary)] file:mr-4 file:rounded-mom-chrome file:border-0 file:bg-[var(--accent-gold-soft)] file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:text-mom-gold"
            />
            <x-input-error class="mt-2" :messages="$errors->get('file')" variant="mom" />
        </div>
        <x-secondary-button variant="mom" type="submit">{{ __('Generate preview') }}</x-secondary-button>
    </form>
</div>

<div class="mom-card overflow-hidden p-0 mb-8">
    <div class="mom-backend-hairline-b px-5 py-4">
        <h3 class="mom-section-title text-base">{{ __('Pending import approvals') }}</h3>
        <p class="mom-subtext mt-1">{{ __('Maker-checker queue — approver must differ from submitter.') }}</p>
    </div>
    @if (($pendingApprovals ?? collect())->isEmpty())
        <p class="mom-body-text p-6 text-[var(--text-muted)]">{{ __('No pending approvals.') }}</p>
    @else
        <div class="mom-table overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">{{ __('File') }}</th>
                        <th class="px-4 py-3">{{ __('Rows') }}</th>
                        <th class="px-4 py-3">{{ __('Requested by') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                    @foreach ($pendingApprovals as $approval)
                        <tr>
                            <td class="px-4 py-3">{{ $approval->id }}</td>
                            <td class="px-4 py-3">{{ $approval->original_filename ?? $approval->workbook ?? $approval->entity_key }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ number_format($approval->total_data_rows) }}</td>
                            <td class="px-4 py-3">{{ $approval->requester?->name ?? '—' }}</td>
                            <td class="px-4 py-3 space-x-3">
                                <form action="{{ route($approveRoutePrefix.'.approve', $approval) }}" method="POST" class="inline">@csrf<button type="submit" class="text-sm font-semibold text-mom-gold hover:underline">{{ __('Approve') }}</button></form>
                                <form action="{{ route($approveRoutePrefix.'.reject', $approval) }}" method="POST" class="inline">@csrf<button type="submit" class="text-sm font-semibold text-[var(--danger)] hover:underline">{{ __('Reject') }}</button></form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="mom-card overflow-hidden p-0">
    <div class="mom-backend-hairline-b px-5 py-4">
        <h3 class="mom-section-title text-base">{{ __('Import history') }}</h3>
        <p class="mom-subtext mt-1">{{ __('Recent import batches, outcomes, and rollback.') }}</p>
    </div>
    @if ($batches->isEmpty())
        <p class="mom-body-text p-6 text-[var(--text-muted)]">{{ __('No import batches yet.') }}</p>
    @else
        <div class="mom-table overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-medium">#</th>
                        <th class="px-4 py-3 font-medium">{{ __('Entity') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-3 font-medium">C/U/S/F</th>
                        <th class="px-4 py-3 font-medium">{{ __('Operator') }}</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                    @foreach ($batches as $batch)
                        <tr>
                            <td class="px-4 py-3 text-[var(--text-primary)]">{{ $batch->id }}</td>
                            <td class="px-4 py-3">{{ $batch->entity_key }}</td>
                            <td class="px-4 py-3">{{ $batch->status }}</td>
                            <td class="px-4 py-3 font-mono text-[12px]">{{ $batch->rows_created }}/{{ $batch->rows_updated }}/{{ $batch->rows_skipped }}/{{ $batch->rows_failed }}</td>
                            <td class="px-4 py-3">{{ $batch->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($batch->isRollbackable())
                                    <form action="{{ route('operations.bulk-import.rollback', $batch) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Rollback this batch?') }}')">
                                        @csrf
                                        <button type="submit" class="text-sm font-semibold text-mom-gold hover:underline">{{ __('Rollback') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
