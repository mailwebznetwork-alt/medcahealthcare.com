<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoAiSignal extends Model
{
    protected $fillable = [
        'business_profile_id',
        'ai_crawl_enabled',
        'llm_visibility_score',
        'entity_consistency_score',
    ];

    protected function casts(): array
    {
        return [
            'ai_crawl_enabled' => 'boolean',
        ];
    }
}
