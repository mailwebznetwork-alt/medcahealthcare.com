<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\PinCode;
use App\Models\Service;
use App\Services\Import\ImportPipeline;
use App\Services\Import\ImportRegistry;
use App\Services\Import\ImportCompareService;
use App\Services\Import\ImportRollbackService;
use App\Services\Import\StagedImportCommitService;
use App\Services\Import\WorkbookImportOrchestrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BulkImportController extends Controller
{
    private const STAGING_KEY = 'bulk_import_staging';

    public function downloadTemplate(string $workbook): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        $file = match ($workbook) {
            'services' => 'services.xlsx',
            'pincodes' => 'pincodes.xlsx',
            default => null,
        };

        if ($file === null) {
            return back()->withErrors(['workbook' => __('Unknown workbook template.')]);
        }

        $path = storage_path('imports/templates/'.$file);
        if (! is_readable($path)) {
            \Illuminate\Support\Facades\Artisan::call('medca:export-import-templates');
        }

        if (! is_readable($path)) {
            return back()->withErrors(['workbook' => __('Template file is not available.')]);
        }

        return response()->download($path, $file);
    }

    public function index(ImportRegistry $registry): View
    {
        return $this->renderWorkspace($registry);
    }

    public function servicesWorkbook(ImportRegistry $registry): View
    {
        $this->authorize('viewAny', Service::class);

        return $this->renderWorkspace($registry, 'services');
    }

    public function pincodesWorkbook(ImportRegistry $registry): View
    {
        $this->authorize('import', PinCode::class);

        return $this->renderWorkspace($registry, 'pincodes');
    }

    public function preview(
        Request $request,
        ImportPipeline $pipeline,
        ImportRegistry $registry,
        WorkbookImportOrchestrator $workbooks,
        ImportCompareService $compareService,
    ): RedirectResponse {
        $lockedWorkbook = $this->lockedWorkbookFromRoute();

        $validator = Validator::make($request->all(), [
            'import_mode' => $lockedWorkbook ? 'nullable|in:workbook' : 'required|in:entity,workbook',
            'entity' => 'required_if:import_mode,entity|string|nullable',
            'workbook' => ($lockedWorkbook || $request->input('import_mode') === 'workbook') ? 'required|string' : 'nullable|string',
            'file' => 'required|file|mimes:csv,txt,xls,xlsx|max:10240',
        ]);

        if ($validator->fails()) {
            throw (new ValidationException($validator))
                ->redirectTo($this->returnUrl());
        }

        $this->discardStaging($request);

        $mode = $lockedWorkbook ? 'workbook' : $request->string('import_mode')->toString();
        $originalName = $request->file('file')->getClientOriginalName();

        if ($mode === 'workbook') {
            $workbookKey = $lockedWorkbook ?? $request->string('workbook')->toString();
            if (! array_key_exists($workbookKey, config('import_registry.workbooks', []))) {
                return redirect()->to($this->returnUrl())->withErrors(['workbook' => __('Invalid workbook type.')]);
            }

            $preview = $workbooks->preview($workbookKey, $request->file('file'));
            if (! $preview['valid']) {
                return redirect()->to($this->returnUrl())->withErrors(['file' => $preview['errors'][0] ?? __('Could not parse workbook.')]);
            }

            $path = $request->file('file')->store('temp/bulk-imports', 'local');
            $absolute = Storage::disk('local')->path($path);
            $compareEntity = $workbookKey === 'pincodes' ? 'pincodes' : 'services';
            $compare = $compareService->compareEntityFile($compareEntity, $absolute);
            session()->put(self::STAGING_KEY, [
                'mode' => 'workbook',
                'workbook' => $workbookKey,
                'path' => $path,
                'original_filename' => $originalName,
                'preview' => $preview,
                'total_data_rows' => $preview['total_data_rows'],
                'compare' => $compare,
            ]);

            return redirect()->to($this->returnUrl());
        }

        $entity = $request->string('entity')->toString();
        if (! in_array($entity, $registry->registeredEntities(), true)) {
            return redirect()->to($this->returnUrl())->withErrors(['entity' => __('Invalid import entity.')]);
        }

        $preview = $pipeline->preview($entity, $request->file('file'));
        if (! $preview['valid']) {
            return redirect()->to($this->returnUrl())->withErrors(['file' => $preview['errors'][0] ?? __('Could not parse file.')]);
        }

        $path = $request->file('file')->store('temp/bulk-imports', 'local');
        $absolute = Storage::disk('local')->path($path);
        $compare = $compareService->compareEntityFile($entity, $absolute);
        session()->put(self::STAGING_KEY, [
            'mode' => 'entity',
            'entity' => $entity,
            'path' => $path,
            'original_filename' => $originalName,
            'preview_rows' => $preview['rows'],
            'total_data_rows' => $preview['total_data_rows'],
            'compare' => $compare,
        ]);

        return redirect()->to($this->returnUrl());
    }

    public function confirm(
        Request $request,
        StagedImportCommitService $committer,
        \App\Services\Import\ImportApprovalService $approvals,
    ): RedirectResponse {
        $staging = session(self::STAGING_KEY);
        if (! is_array($staging) || empty($staging['path'])) {
            return redirect()->to($this->returnUrl())->withErrors(['file' => __('Upload and preview a file before confirming.')]);
        }

        if (! config('import_registry.workflow.requires_approval', true)) {
            return redirect()->to($this->returnUrl())->withErrors(['file' => __('Import approval is disabled in config.')]);
        }

        $absolute = Storage::disk('local')->path($staging['path']);
        if (! is_readable($absolute)) {
            $this->discardStaging($request);

            return redirect()->to($this->returnUrl())->withErrors(['file' => __('Staged file no longer available.')]);
        }

        if (config('import_registry.workflow.maker_checker_enabled', true)) {
            $approvals->submitForApproval($staging, $request->user());
            $request->session()->forget(self::STAGING_KEY);

            return redirect()
                ->to($this->returnUrl())
                ->with('import_submitted_for_approval', true);
        }

        if ($this->shouldCommitAsync($staging)) {
            return $this->dispatchAsyncCommit($request, $staging);
        }

        $result = $committer->commit($staging, $request->user()?->id);

        Storage::disk('local')->delete($staging['path']);
        $this->discardStaging($request);

        return redirect()
            ->to($this->returnUrl())
            ->with('import_result', $result);
    }

    public function approveImport(
        Request $request,
        \App\Services\Import\ImportApprovalService $approvals,
        int $approval,
    ): RedirectResponse {
        $record = \App\Models\ImportApprovalRequest::query()->findOrFail($approval);
        $result = $approvals->approve($record, $request->user());

        if (! ($result['success'] ?? false)) {
            return back()->withErrors(['approval' => $result['errors'][0] ?? __('Approval failed.')]);
        }

        return back()->with('import_result', $result);
    }

    public function rejectImport(
        Request $request,
        \App\Services\Import\ImportApprovalService $approvals,
        int $approval,
    ): RedirectResponse {
        $record = \App\Models\ImportApprovalRequest::query()->findOrFail($approval);
        $ok = $approvals->reject($record, $request->user(), $request->string('reason')->toString());

        if (! $ok) {
            return back()->withErrors(['approval' => __('Could not reject import.')]);
        }

        return back()->with('import_rejected', true);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->discardStaging($request);

        return redirect()->to($this->returnUrl());
    }

    public function rollback(Request $request, ImportRollbackService $rollback, int $batch): RedirectResponse
    {
        $result = $rollback->rollback($batch);

        if (! $result['success'] && $result['reverted'] === 0) {
            return back()->withErrors(['rollback' => $result['errors'][0] ?? __('Rollback failed.')]);
        }

        return back()->with('rollback_result', $result);
    }

    private function renderWorkspace(ImportRegistry $registry, ?string $lockedWorkbook = null): View
    {
        $view = match ($lockedWorkbook) {
            'services' => 'operations.services.bulk-import',
            'pincodes' => 'operations.pin-codes.bulk-import',
            default => 'operations.bulk-import.index',
        };

        return view($view, [
            'entities' => $registry->readinessMatrix(),
            'workbooks' => config('import_registry.workbooks', []),
            'batches' => $this->batchesForWorkbook($lockedWorkbook),
            'staging' => session(self::STAGING_KEY),
            'lockedWorkbook' => $lockedWorkbook,
            'pendingApprovals' => \App\Models\ImportApprovalRequest::query()
                ->with(['requester:id,name', 'approver:id,name'])
                ->where('status', \App\Enums\ImportApprovalStatus::Pending)
                ->orderByDesc('id')
                ->limit(20)
                ->get(),
            'approveRoutePrefix' => $lockedWorkbook === 'services'
                ? 'operations.services.bulk-import'
                : ($lockedWorkbook === 'pincodes' ? 'operations.pin-codes.bulk-import' : 'operations.bulk-import'),
        ]);
    }

  /**
     * @return Collection<int, ImportBatch>
     */
    private function batchesForWorkbook(?string $workbook): Collection
    {
        $entities = match ($workbook) {
            'services' => ['categories', 'services', 'sub_services'],
            'pincodes' => ['pincodes', 'geo', 'mappings'],
            default => null,
        };

        $query = ImportBatch::query()
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit(25);

        if ($entities !== null) {
            $query->whereIn('entity_key', $entities);
        }

        return $query->get();
    }

    private function lockedWorkbookFromRoute(): ?string
    {
        if (request()->routeIs('operations.services.bulk-import*')) {
            return 'services';
        }

        if (request()->routeIs('operations.pin-codes.bulk-import*')) {
            return 'pincodes';
        }

        return null;
    }

    private function returnUrl(): string
    {
        return match ($this->lockedWorkbookFromRoute()) {
            'services' => route('operations.services.bulk-import'),
            'pincodes' => route('operations.pin-codes.bulk-import'),
            default => route('operations.bulk-import.index'),
        };
    }

    private function discardStaging(Request $request): void
    {
        $staging = $request->session()->pull(self::STAGING_KEY);
        if (is_array($staging) && isset($staging['path']) && is_string($staging['path'])) {
            Storage::disk('local')->delete($staging['path']);
        }
    }

    /**
     * @param  array<string, mixed>  $staging
     */
    private function shouldCommitAsync(array $staging): bool
    {
        if (! config('import_registry.workflow.async_commit', true)) {
            return false;
        }

        if (($staging['mode'] ?? '') === 'workbook') {
            return true;
        }

        $threshold = (int) config('import_registry.workflow.async_commit_row_threshold', 50);

        return (int) ($staging['total_data_rows'] ?? 0) >= $threshold;
    }

    /**
     * @param  array<string, mixed>  $staging
     */
    private function dispatchAsyncCommit(Request $request, array $staging): RedirectResponse
    {
        $leanStaging = $this->leanStagingPayload($staging);
        $userId = $request->user()?->id;
        $rows = (int) ($staging['total_data_rows'] ?? 0);
        $filename = $staging['original_filename'] ?? null;

        // Drop heavy preview payload from session before redirect (prevents slow session writes).
        $request->session()->forget(self::STAGING_KEY);

        // Run import after the HTTP response is sent — nginx gets an instant redirect (no 504).
        app()->terminating(function () use ($leanStaging, $userId): void {
            try {
                $result = app(StagedImportCommitService::class)->commit($leanStaging, $userId);

                if (isset($leanStaging['path']) && is_string($leanStaging['path'])) {
                    Storage::disk('local')->delete($leanStaging['path']);
                }

                \Illuminate\Support\Facades\Log::info('Background bulk import finished', [
                    'created' => $result['created'] ?? 0,
                    'updated' => $result['updated'] ?? 0,
                    'skipped' => $result['skipped'] ?? 0,
                    'failed' => $result['failed'] ?? 0,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Background bulk import failed: '.$e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });

        return redirect()
            ->to($this->returnUrl())
            ->with('import_async_started', [
                'rows' => $rows,
                'filename' => $filename,
            ]);
    }

    /**
     * Keep only fields required for commit — omit preview blobs stored in session.
     *
     * @param  array<string, mixed>  $staging
     * @return array<string, mixed>
     */
    private function leanStagingPayload(array $staging): array
    {
        return array_filter([
            'mode' => $staging['mode'] ?? null,
            'workbook' => $staging['workbook'] ?? null,
            'entity' => $staging['entity'] ?? null,
            'path' => $staging['path'] ?? null,
            'original_filename' => $staging['original_filename'] ?? null,
            'total_data_rows' => $staging['total_data_rows'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
