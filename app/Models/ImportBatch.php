<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'entity_key',
        'user_id',
        'original_filename',
        'status',
        'rows_created',
        'rows_updated',
        'rows_skipped',
        'rows_failed',
        'error_summary',
        'committed_at',
        'rolled_back_at',
    ];

    protected function casts(): array
    {
        return [
            'rows_created' => 'integer',
            'rows_updated' => 'integer',
            'rows_skipped' => 'integer',
            'rows_failed' => 'integer',
            'committed_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ImportBatchEntry::class);
    }

    public function isRollbackable(): bool
    {
        return $this->status === 'committed'
            && $this->rolled_back_at === null
            && config('import_registry.workflow.rollback_enabled', true);
    }
}
