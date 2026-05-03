<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PinCodeImportLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'original_filename',
        'rows_created',
        'rows_skipped',
        'rows_failed',
        'status',
        'error_summary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rows_created' => 'integer',
            'rows_skipped' => 'integer',
            'rows_failed' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
