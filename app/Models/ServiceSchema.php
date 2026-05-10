<?php

namespace App\Models;

use Database\Factories\ServiceSchemaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_id',
    'schema_type',
    'schema_json',
])]
class ServiceSchema extends Model
{
    /** @use HasFactory<ServiceSchemaFactory> */
    use HasFactory;

    protected $table = 'service_schema';

    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
