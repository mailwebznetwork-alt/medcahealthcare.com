<x-operations.workspace>
    <h2 class="mom-section-title mb-2">{{ __('Bulk import') }}</h2>
    <p class="mom-subtext mb-8 max-w-3xl text-[var(--text-secondary)]">
        {{ __('Upload a UTF-8 CSV (Excel: Save As CSV). Preview the first rows, confirm, then track outcomes in history below.') }}
    </p>

    @if ($errors->has('file'))
        <div class="mom-card mb-8 border border-[rgba(220,38,38,0.25)] p-4" role="alert">
            <p class="mom-section-title text-base text-[var(--danger)]">{{ __('Import blocked') }}</p>
            <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ $errors->first('file') }}</p>
        </div>
    @endif

    @if (is_array($staging) && ! empty($staging['preview_rows']))
        <div class="mom-card mb-8 overflow-hidden p-0">
            <div class="border-b border-[rgba(255,255,255,0.045)] px-5 py-4">
                <h3 class="mom-section-title text-base">{{ __('Import preview') }}</h3>
                <p class="mom-subtext mt-1">
                    {{ __('Showing up to :n sample rows. Total data rows detected: :t.', ['n' => count($staging['preview_rows']), 't' => number_format((int) ($staging['total_data_rows'] ?? 0))]) }}
                </p>
            </div>
            <div class="mom-table overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-[13px]">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('Line') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Pincode') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Area') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('City') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Note') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                        @foreach ($staging['preview_rows'] as $row)
                            <tr>
                                <td class="px-4 py-3 font-mono text-[12px] text-[var(--text-primary)]">{{ $row['line'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-mom-pill border border-[rgba(255,255,255,0.06)] px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide">{{ $row['status'] }}</span>
                                </td>
                                <td class="px-4 py-3">{{ $row['pincode'] ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $row['area_name'] ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $row['city'] ?? '—' }}</td>
                                <td class="px-4 py-3 mom-micro">{{ $row['detail'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-[rgba(255,255,255,0.045)] px-5 py-4">
                <form method="post" action="{{ route('operations.pin-codes.bulk-import.cancel') }}" class="inline">
                    @csrf
                    <x-secondary-button variant="mom" type="submit">{{ __('Cancel staging') }}</x-secondary-button>
                </form>
                <form method="post" action="{{ route('operations.pin-codes.bulk-import.confirm') }}" class="flex flex-wrap items-center gap-4">
                    @csrf
                    <label class="flex cursor-pointer items-center gap-2 text-[13px] text-[var(--text-secondary)]">
                        <input type="checkbox" name="confirm_import" value="1" class="h-4 w-4 rounded border-[rgba(255,255,255,0.12)] bg-[rgba(28,22,18,0.75)] text-mom-gold focus:ring-1 focus:ring-[rgba(212,169,95,0.35)]" />
                        {{ __('I confirm this import should run against the live directory.') }}
                    </label>
                    <x-primary-button variant="mom" type="submit">{{ __('Run import') }}</x-primary-button>
                </form>
            </div>
            <x-input-error class="px-5 pb-4" :messages="$errors->get('confirm_import')" variant="mom" />
        </div>
    @endif

    <div class="mom-card mb-8 p-6">
        <h3 class="mom-section-title text-base">{{ __('Upload & preview') }}</h3>
        <p class="mom-subtext mt-2 max-w-3xl text-[var(--text-secondary)]">
            {{ __('Required columns: pincode, area_name, city. Optional: locality, serviceability, delivery_charge, meta_title, meta_description, seo_keywords. Duplicates are skipped.') }}
        </p>
        <form method="post" action="{{ route('operations.pin-codes.bulk-import.preview') }}" enctype="multipart/form-data" class="mt-6 flex flex-wrap items-end gap-4">
            @csrf
            <div class="min-w-[12rem] flex-1">
                <x-input-label for="file" :value="__('CSV file')" variant="mom" />
                <input
                    id="file"
                    name="file"
                    type="file"
                    accept=".csv,text/csv,text/plain"
                    required
                    class="mt-2 block w-full text-sm text-[var(--text-secondary)] file:mr-4 file:rounded-mom-md file:border-0 file:bg-[rgba(212,169,95,0.18)] file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:text-[#0a0a0a]"
                />
                <x-input-error class="mt-2" :messages="$errors->get('file')" variant="mom" />
            </div>
            <x-secondary-button variant="mom" type="submit">{{ __('Generate preview') }}</x-secondary-button>
        </form>
    </div>

    <div class="mom-card overflow-hidden p-0">
        <div class="border-b border-[rgba(255,255,255,0.045)] px-5 py-4">
            <h3 class="mom-section-title text-base">{{ __('Import history') }}</h3>
            <p class="mom-subtext mt-1">{{ __('Status, row counts, and errors from prior confirmed runs.') }}</p>
        </div>
        @if ($importLogs->isEmpty())
            <p class="mom-body-text p-6 text-[var(--text-muted)]">{{ __('No completed imports yet.') }}</p>
        @else
            <div class="mom-table overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-[13px]">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('When') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Operator') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('File') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Created') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Skipped') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Failed') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                        @foreach ($importLogs as $log)
                            <tr>
                                <td class="px-4 py-3 text-[var(--text-primary)]">{{ $log->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3">{{ $log->user?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="max-w-[12rem] truncate font-mono text-[12px]" title="{{ $log->original_filename }}">{{ $log->original_filename }}</span>
                                </td>
                                <td class="px-4 py-3">{{ number_format($log->rows_created) }}</td>
                                <td class="px-4 py-3">{{ number_format($log->rows_skipped) }}</td>
                                <td class="px-4 py-3">{{ number_format($log->rows_failed) }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-mom-pill border border-[rgba(255,255,255,0.06)] px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide">{{ $log->status }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-operations.workspace>
