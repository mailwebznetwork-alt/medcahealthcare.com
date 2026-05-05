<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoTechnical extends Model
{
    protected $table = 'seo_technical';

    protected $fillable = [
        'robots_enabled',
        'sitemap_enabled',
        'canonical_mode',
        'robots_content',
        'sitemap_url',
    ];

    protected function casts(): array
    {
        return [
            'robots_enabled' => 'boolean',
            'sitemap_enabled' => 'boolean',
        ];
    }
}
