<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_category_id',
    'schema_type',
    'schema_json',
])]
class ServiceCategorySchema extends Model
{
    protected $table = 'service_category_schema';

    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
}
