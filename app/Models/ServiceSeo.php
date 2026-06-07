<?php

namespace App\Models;

use Database\Factories\ServiceSeoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_id',
    'meta_title',
    'meta_description',
    'focus_keywords',
    'h1',
    'h2',
    'h3',
    'ai_context',
    'search_intent',
    'secondary_keywords',
    'canonical_url',
    'robots_index',
    'og_title',
    'og_description',
    'og_image',
    'twitter_card',
    'seo_score',
    'aeo_score',
    'geo_score',
    'schema_health_score',
    'content_quality_score',
    'local_seo_score',
    'ai_discovery_score',
    'image_seo_score',
    'entity_graph',
    'seo_recommendations',
    'entity_tags',
    'geo_entities',
])]
class ServiceSeo extends Model
{
    /** @use HasFactory<ServiceSeoFactory> */
    use HasFactory;

    protected $table = 'service_seo';

    protected function casts(): array
    {
        return [
            'focus_keywords' => 'array',
            'secondary_keywords' => 'array',
            'h2' => 'array',
            'h3' => 'array',
            'robots_index' => 'boolean',
            'seo_score' => 'integer',
            'aeo_score' => 'integer',
            'geo_score' => 'integer',
            'schema_health_score' => 'integer',
            'content_quality_score' => 'integer',
            'local_seo_score' => 'integer',
            'ai_discovery_score' => 'integer',
            'image_seo_score' => 'integer',
            'entity_graph' => 'array',
            'seo_recommendations' => 'array',
            'entity_tags' => 'array',
            'geo_entities' => 'array',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
