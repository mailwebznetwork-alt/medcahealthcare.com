<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoTechnical extends Model
{
    protected $table = 'seo_technical';

    protected $fillable = [
        'business_profile_id',
        'robots_txt',
        'sitemap_enabled',
        'canonical_url',
        'indexable',
    ];

    protected function casts(): array
    {
        return [
            'sitemap_enabled' => 'boolean',
            'indexable' => 'boolean',
        ];
    }
}
