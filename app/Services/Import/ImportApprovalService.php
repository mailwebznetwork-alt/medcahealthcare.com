<?php

namespace App\Services\Import;

use App\Enums\ImportApprovalStatus;
use App\Models\ImportApprovalRequest;
use App\Models\User;
use App\Services\Import\StagedImportCommitService;
use Illuminate\Support\Facades\Storage;

class ImportApprovalService
{
    public function __construct(
        private readonly StagedImportCommitService $committer,
    ) {}

    /**
     * @param  array<string, mixed>  $staging
     */
    public function submitForApproval(array $staging, User $requester): ImportApprovalRequest
    {
        $path = (string) ($staging['path'] ?? '');
        $checksum = is_readable(Storage::disk('local')->path($path))
            ? hash_file('sha256', Storage::disk('local')->path($path))
            : null;

        return ImportApprovalRequest::query()->create([
            'requested_by' => $requester->id,
            'status' => ImportApprovalStatus::Pending,
            'entity_key' => $staging['entity'] ?? null,
            'workbook' => $staging['workbook'] ?? null,
            'staging_path' => $path,
            'original_filename' => $staging['original_filename'] ?? null,
            'total_data_rows' => (int) ($staging['total_data_rows'] ?? 0),
            'staging_checksum' => $checksum,
            'staging_meta' => [
                'mode' => $staging['mode'] ?? null,
            ],
            'requested_at' => now(),
        ]);
    }

    public function canApprove(User $user, ImportApprovalRequest $request): bool
    {
        if ($user->id === $request->requested_by) {
            return false;
        }

        $role = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;

        return in_array($role, ['super_admin', 'admin', 'manager', 'medical_reviewer'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function approve(ImportApprovalRequest $request, User $approver): array
    {
        if ($request->status !== ImportApprovalStatus::Pending) {
            return ['success' => false, 'errors' => [__('Import approval is not pending.')]];
        }

        if (! $this->canApprove($approver, $request)) {
            return ['success' => false, 'errors' => [__('You cannot approve this import.')]];
        }

        $absolute = Storage::disk('local')->path($request->staging_path);
        if (! is_readable($absolute)) {
            return ['success' => false, 'errors' => [__('Staged file no longer available.')]];
        }

        $staging = [
            'mode' => $request->staging_meta['mode'] ?? ($request->workbook ? 'workbook' : 'entity'),
            'workbook' => $request->workbook,
            'entity' => $request->entity_key,
            'path' => $request->staging_path,
            'original_filename' => $request->original_filename,
            'total_data_rows' => $request->total_data_rows,
        ];

        $result = $this->committer->commit($staging, $request->requested_by);

        $request->forceFill([
            'status' => ImportApprovalStatus::Approved,
            'approved_by' => $approver->id,
            'import_batch_id' => $result['batch_id'] ?? null,
            'resolved_at' => now(),
        ])->save();

        Storage::disk('local')->delete($request->staging_path);

        return array_merge($result, ['success' => true]);
    }

    public function reject(ImportApprovalRequest $request, User $approver, ?string $reason = null): bool
    {
        if (! $this->canApprove($approver, $request)) {
            return false;
        }

        $request->forceFill([
            'status' => ImportApprovalStatus::Rejected,
            'approved_by' => $approver->id,
            'rejection_reason' => $reason,
            'resolved_at' => now(),
        ])->save();

        Storage::disk('local')->delete($request->staging_path);

        return true;
    }
}
