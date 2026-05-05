<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoAiSignal extends Model
{
    protected $fillable = [
        'ai_crawl_enabled',
        'llm_visibility_score',
        'entity_consistency_score',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'ai_crawl_enabled' => 'boolean',
            'notes' => 'array',
        ];
    }
}
