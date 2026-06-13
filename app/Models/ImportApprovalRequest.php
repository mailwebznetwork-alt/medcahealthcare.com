<?php

namespace App\Models;

use App\Enums\ImportApprovalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'requested_by',
    'approved_by',
    'status',
    'entity_key',
    'workbook',
    'staging_path',
    'original_filename',
    'total_data_rows',
    'staging_checksum',
    'staging_meta',
    'import_batch_id',
    'rejection_reason',
    'requested_at',
    'resolved_at',
])]
class ImportApprovalRequest extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ImportApprovalStatus::class,
            'total_data_rows' => 'integer',
            'staging_meta' => 'array',
            'requested_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
