<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatchEntry extends Model
{
    protected $fillable = [
        'import_batch_id',
        'action',
        'entity_type',
        'entity_id',
        'previous_state',
        'line_number',
    ];

    protected function casts(): array
    {
        return [
            'previous_state' => 'array',
            'line_number' => 'integer',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
