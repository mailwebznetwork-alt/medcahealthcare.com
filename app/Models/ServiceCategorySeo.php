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
    'h1',
    'h2',
    'h3',
    'search_intent',
    'schema_health_score',
    'content_quality_score',
    'local_seo_score',
    'image_seo_score',
    'seo_recommendations',
    'geo_entities',
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
            'h2' => 'array',
            'h3' => 'array',
            'schema_health_score' => 'integer',
            'content_quality_score' => 'integer',
            'local_seo_score' => 'integer',
            'image_seo_score' => 'integer',
            'seo_recommendations' => 'array',
            'geo_entities' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
}
