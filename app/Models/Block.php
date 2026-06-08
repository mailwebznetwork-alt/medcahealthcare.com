<?php

namespace App\Models;

use App\Concerns\HasAdminLifecycle;
use Database\Factories\BlockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Block extends Model
{
    /** @use HasFactory<BlockFactory> */
    use HasAdminLifecycle;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'block_name',
        'block_slug',
        'description',
        'block_type',
        'code',
        'custom_css',
        'schema_json',
        'settings_json',
        'is_active',
        'is_managed',
        'lifecycle_state',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
            'settings_json' => 'array',
            'is_active' => 'boolean',
            'is_managed' => 'boolean',
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
