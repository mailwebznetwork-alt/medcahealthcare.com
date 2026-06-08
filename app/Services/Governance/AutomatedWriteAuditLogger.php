<?php

namespace App\Services\Governance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutomatedWriteAuditLogger
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        string $process,
        string $action,
        ?string $table = null,
        ?int $recordId = null,
        ?string $recordKey = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        string $outcome = 'applied',
        ?string $reason = null,
    ): void {
        if (! config('governance.audit_automated_writes', true)) {
            return;
        }

        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('automated_write_audits')) {
                return;
            }

            DB::table('automated_write_audits')->insert([
                'process' => $process,
                'action' => $action,
                'table_name' => $table,
                'record_id' => $recordId,
                'record_key' => $recordKey,
                'old_values' => $oldValues !== null ? json_encode($oldValues) : null,
                'new_values' => $newValues !== null ? json_encode($newValues) : null,
                'outcome' => $outcome,
                'reason' => $reason,
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Automated write audit could not be persisted.', [
                'process' => $process,
                'action' => $action,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function blocked(
        string $process,
        string $action,
        string $table,
        ?int $recordId,
        ?string $recordKey,
        string $reason,
    ): void {
        $this->log(
            process: $process,
            action: $action,
            table: $table,
            recordId: $recordId,
            recordKey: $recordKey,
            outcome: 'blocked',
            reason: $reason,
        );
    }
}
