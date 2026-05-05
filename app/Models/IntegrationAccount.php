<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationAccount extends Model
{
    protected $fillable = [
        'integration_id',
        'label',
        'account_identifier',
        'credentials',
        'is_enabled',
        'last_used_at',
    ];

    protected $casts = [
        'credentials' => 'array',
        'is_enabled' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }
}
