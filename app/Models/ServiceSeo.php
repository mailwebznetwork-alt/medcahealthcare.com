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
            'h2' => 'array',
            'h3' => 'array',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
