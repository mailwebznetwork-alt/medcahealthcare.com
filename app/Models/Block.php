<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Block extends Model
{
    protected $fillable = [
        'uuid',
        'block_name',
        'block_slug',
        'description',
        'block_type',
        'code',
        'schema_json',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Block $block): void {
            if (empty($block->uuid)) {
                $block->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'block_slug';
    }
}
