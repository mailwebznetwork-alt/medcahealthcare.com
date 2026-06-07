<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sub_service_id',
    'question',
    'answer',
    'sort_order',
])]
class SubServiceFaq extends Model
{
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function subService(): BelongsTo
    {
        return $this->belongsTo(SubService::class);
    }
}
