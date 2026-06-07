<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'page_id',
    'entity_type',
    'entity_id',
    'registry_key',
    'page_category',
    'owner',
    'source',
    'public_path',
    'is_listed',
    'visibility_snapshot',
    'ownership_snapshot',
])]
class PageRegistry extends Model
{
    protected $table = 'page_registry';

    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'is_listed' => 'boolean',
            'visibility_snapshot' => 'array',
            'ownership_snapshot' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
