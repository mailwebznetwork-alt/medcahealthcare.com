<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_category_id',
    'meta_title',
    'meta_description',
    'focus_keywords',
    'secondary_keywords',
    'canonical_url',
    'robots_index',
    'og_title',
    'og_description',
    'og_image',
    'twitter_card',
    'ai_context',
    'aeo_question',
    'aeo_answer',
    'seo_score',
    'aeo_score',
    'geo_score',
    'ai_discovery_score',
    'geo_signals',
    'aeo_signals',
    'entity_tags',
])]
class ServiceCategorySeo extends Model
{
    protected $table = 'service_category_seo';

    protected function casts(): array
    {
        return [
            'focus_keywords' => 'array',
            'secondary_keywords' => 'array',
            'robots_index' => 'boolean',
            'seo_score' => 'integer',
            'aeo_score' => 'integer',
            'geo_score' => 'integer',
            'ai_discovery_score' => 'integer',
            'geo_signals' => 'array',
            'aeo_signals' => 'array',
            'entity_tags' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
}
