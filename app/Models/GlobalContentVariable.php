<?php

namespace App\Models;

use App\Services\Deployment\GlobalContentVariableRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalContentVariable extends Model
{
    protected $fillable = [
        'key',
        'label',
        'value',
        'updated_by_id',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    protected static function booted(): void
    {
        static::saved(fn () => GlobalContentVariableRepository::forgetCache());
        static::deleted(fn () => GlobalContentVariableRepository::forgetCache());
    }
}
