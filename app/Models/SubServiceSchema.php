<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sub_service_id',
    'schema_type',
    'schema_json',
])]
class SubServiceSchema extends Model
{
    protected $table = 'sub_service_schema';

    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
        ];
    }

    public function subService(): BelongsTo
    {
        return $this->belongsTo(SubService::class);
    }
}
