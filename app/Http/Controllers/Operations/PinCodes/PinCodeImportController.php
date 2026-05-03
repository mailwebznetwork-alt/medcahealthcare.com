<?php

namespace App\Http\Controllers\Operations\PinCodes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\PinCodes\ConfirmPinCodeImportRequest;
use App\Http\Requests\Operations\PinCodes\ImportPinCodesRequest;
use App\Models\PinCode;
use App\Models\PinCodeImportLog;
use App\Services\PinCodes\PinCodeCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PinCodeImportController extends Controller
{
    private const STAGING_SESSION_KEY = 'pin_code_import_staging';

    public function create(Request $request): View
    {
        $this->authorize('import', PinCode::class);

        $logs = PinCodeImportLog::query()
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $staging = $request->session()->get(self::STAGING_SESSION_KEY);

        return view('operations.pin-codes.bulk-import', [
            'importLogs' => $logs,
            'staging' => is_array($staging) ? $staging : null,
        ]);
    }

    public function preview(ImportPinCodesRequest $request, PinCodeCsvImporter $importer): RedirectResponse
    {
        $this->discardStaging($request);

        $preview = $importer->preview($request->file('file'));
        if (! $preview['valid']) {
            return redirect()
                ->route('operations.pin-codes.bulk-import')
                ->withErrors(['file' => $preview['errors'][0] ?? __('Could not parse CSV.')]);
        }

        $relativePath = $request->file('file')->store('temp/pin-imports', 'local');
        $request->session()->put(self::STAGING_SESSION_KEY, [
            'disk' => 'local',
            'path' => $relativePath,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'preview_rows' => $preview['rows'],
            'total_data_rows' => $preview['total_data_rows'],
        ]);

        return redirect()->route('operations.pin-codes.bulk-import');
    }

    public function confirm(ConfirmPinCodeImportRequest $request, PinCodeCsvImporter $importer): RedirectResponse
    {
        $staging = $request->session()->get(self::STAGING_SESSION_KEY);
        if (! is_array($staging) || empty($staging['path'])) {
            return redirect()
                ->route('operations.pin-codes.bulk-import')
                ->withErrors(['file' => __('Upload and preview a CSV before confirming the import.')]);
        }

        $relative = (string) $staging['path'];
        if (! str_starts_with($relative, 'temp/pin-imports/')) {
            $this->discardStaging($request);

            return redirect()
                ->route('operations.pin-codes.bulk-import')
                ->withErrors(['file' => __('Invalid staged import. Please upload again.')]);
        }

        $absolutePath = Storage::disk('local')->path($relative);
        if (! is_readable($absolutePath)) {
            $this->discardStaging($request);

            return redirect()
                ->route('operations.pin-codes.bulk-import')
                ->withErrors(['file' => __('The staged file is no longer available. Please upload again.')]);
        }

        $result = $importer->import($absolutePath);

        Storage::disk('local')->delete($relative);
        $this->discardStaging($request);

        $originalName = (string) ($staging['original_filename'] ?? 'import.csv');
        $this->writeImportLog($request, $originalName, $result);

        return redirect()
            ->route('operations.pin-codes.overview')
            ->with('import_result', $result);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->authorize('import', PinCode::class);
        $this->discardStaging($request);

        return redirect()->route('operations.pin-codes.bulk-import');
    }

    /**
     * @param  array{created: int, skipped: int, failed: int, errors: list<string>}  $result
     */
    private function writeImportLog(Request $request, string $originalFilename, array $result): void
    {
        $errors = $result['errors'] ?? [];
        $hasBlocking = $result['created'] === 0 && $result['skipped'] === 0 && $result['failed'] === 0 && $errors !== [];

        $status = $hasBlocking
            ? 'failed'
            : (($result['failed'] ?? 0) > 0 || $errors !== [] ? 'completed_with_warnings' : 'completed');

        $summary = $errors !== [] ? implode("\n", array_slice($errors, 0, 40)) : null;

        PinCodeImportLog::query()->create([
            'user_id' => $request->user()?->id,
            'original_filename' => $originalFilename,
            'rows_created' => (int) ($result['created'] ?? 0),
            'rows_skipped' => (int) ($result['skipped'] ?? 0),
            'rows_failed' => (int) ($result['failed'] ?? 0),
            'status' => $status,
            'error_summary' => $summary,
        ]);
    }

    private function discardStaging(Request $request): void
    {
        $staging = $request->session()->pull(self::STAGING_SESSION_KEY);
        if (is_array($staging) && isset($staging['path']) && is_string($staging['path'])) {
            if (str_starts_with($staging['path'], 'temp/pin-imports/')) {
                Storage::disk('local')->delete($staging['path']);
            }
        }
    }
}
