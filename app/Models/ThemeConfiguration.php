<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class ThemeConfiguration extends Model
{
    private const CACHE_KEY = 'theme.configuration.singleton_id';

    protected $fillable = [
        'published_public',
        'published_shape',
        'draft_public',
        'draft_shape',
        'published_admin',
        'draft_admin',
        'branding',
        'draft_branding',
        'typography',
        'draft_typography',
        'header_preset',
        'layout_preset',
        'draft_header_preset',
        'draft_layout_preset',
        'active_preset_slug',
        'active_style_pack',
        'draft_style_pack',
        'updated_by_id',
        'published_by_id',
        'draft_updated_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_public' => 'array',
            'published_shape' => 'array',
            'draft_public' => 'array',
            'draft_shape' => 'array',
            'published_admin' => 'array',
            'draft_admin' => 'array',
            'branding' => 'array',
            'draft_branding' => 'array',
            'typography' => 'array',
            'draft_typography' => 'array',
            'draft_updated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        /** @var int $id */
        $id = Cache::rememberForever(self::CACHE_KEY, function (): int {
            return (int) self::query()->firstOrCreate([], [
                'header_preset' => 'classic_digital growth platform',
                'layout_preset' => 'contained',
            ])->getKey();
        });

        return self::query()->findOrFail($id);
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(config('theme_management.cache_key'));
        Cache::forget(config('theme_management.cache_key').'.tokens');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_id');
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetCache());
        static::deleted(fn () => self::forgetCache());
    }
}
