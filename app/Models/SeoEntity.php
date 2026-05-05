<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoEntity extends Model
{
    protected $fillable = [
        'business_profile_id',
        'organization_name',
        'logo',
        'same_as',
        'meta_title',
        'meta_description',
        'og_image_url',
        'custom_json_ld',
    ];

    protected function casts(): array
    {
        return [
            'same_as' => 'array',
            'custom_json_ld' => 'array',
        ];
    }
}
