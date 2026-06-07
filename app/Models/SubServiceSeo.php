<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sub_service_id',
    'meta_title',
    'meta_description',
    'h1',
    'focus_keywords',
    'secondary_keywords',
    'canonical_url',
    'robots_index',
    'ai_context',
    'seo_score',
    'aeo_score',
    'geo_score',
    'schema_health_score',
    'entity_tags',
    'geo_entities',
])]
class SubServiceSeo extends Model
{
    protected $table = 'sub_service_seo';

    protected function casts(): array
    {
        return [
            'focus_keywords' => 'array',
            'secondary_keywords' => 'array',
            'robots_index' => 'boolean',
            'seo_score' => 'integer',
            'aeo_score' => 'integer',
            'geo_score' => 'integer',
            'schema_health_score' => 'integer',
            'entity_tags' => 'array',
            'geo_entities' => 'array',
        ];
    }

    public function subService(): BelongsTo
    {
        return $this->belongsTo(SubService::class);
    }
}
