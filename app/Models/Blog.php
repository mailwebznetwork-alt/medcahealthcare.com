<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    protected $fillable = [
        'uuid',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'author_name',
        'published_at',
        'meta_title',
        'meta_description',
        'keywords',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'aeo_question',
        'aeo_answer',
        'schema_json',
        'is_published',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Blog $blog): void {
            if (empty($blog->uuid)) {
                $blog->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
