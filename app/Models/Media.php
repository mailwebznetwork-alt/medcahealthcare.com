<?php

namespace App\Models;

use App\Services\Media\MediaPublicUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'uuid',
        'file_name',
        'file_path',
        'file_url',
        'file_type',
        'file_size',
        'file_hash',
        'legacy_path',
        'title',
        'alt_text',
        'description',
        'optimized_path',
        'webp_path',
        'small_path',
        'medium_path',
        'large_path',
        'blur_path',
        'thumbnail_path',
        'avif_path',
        'width',
        'height',
        'mime_type',
        'caption',
        'tags',
        'category',
        'image_seo_score',
        'source_module',
        'uploaded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'tags' => 'array',
            'image_seo_score' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Media $media): void {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });

        static::deleting(function (Media $media): void {
            $dir = dirname($media->file_path);
            if ($dir === '.' || $dir === '') {
                return;
            }
            Storage::disk('public')->deleteDirectory($dir);
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return HasMany<MediaUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    /**
     * Relative storage path used for references (WebP preferred).
     */
    public function referencePath(): string
    {
        foreach ([$this->webp_path, $this->large_path, $this->optimized_path, $this->file_path] as $path) {
            if (is_string($path) && $path !== '') {
                return $path;
            }
        }

        return (string) $this->file_path;
    }

    public function publicUrlFor(?string $relativePath): string
    {
        return MediaPublicUrl::forPath($relativePath);
    }

    /**
     * Preferred delivery URL for images: WebP → optimized JPEG → original.
     */
    public function preferredImageUrl(): string
    {
        if ($this->file_type !== 'image') {
            return MediaPublicUrl::forPath($this->file_path);
        }

        foreach ([$this->webp_path, $this->optimized_path, $this->file_path] as $path) {
            if ($path !== null && $path !== '') {
                return MediaPublicUrl::forPath($path);
            }
        }

        return '';
    }

    public function snippetHtmlBasic(): string
    {
        $src = e($this->preferredImageUrl());
        $alt = e($this->alt_text ?? '');

        return '<img src="'.$src.'" loading="lazy" alt="'.$alt.'">';
    }

    public function snippetHtmlResponsive(): string
    {
        if ($this->file_type !== 'image') {
            return $this->snippetHtmlBasic();
        }

        $fallback = MediaPublicUrl::forPath($this->webp_path ?? $this->optimized_path ?? $this->file_path);
        if ($fallback === '') {
            return $this->snippetHtmlBasic();
        }

        $small = MediaPublicUrl::forPath($this->small_path);
        $medium = MediaPublicUrl::forPath($this->medium_path);
        $large = MediaPublicUrl::forPath($this->large_path ?? $this->webp_path);

        $pieces = array_values(array_filter([
            $small !== '' ? $small.' 480w' : null,
            $medium !== '' ? $medium.' 768w' : null,
            $large !== '' ? $large.' 1200w' : null,
        ]));

        if ($pieces === []) {
            return $this->snippetHtmlBasic();
        }

        $src = $small !== '' ? $small : $fallback;
        $alt = e($this->alt_text ?? '');

        return '<img src="'.e($src).'" srcset="'.e(implode(', ', $pieces)).'" sizes="(max-width: 768px) 100vw, 1200px" loading="lazy" alt="'.$alt.'">';
    }

    public function snippetHtmlBlurPlaceholder(): string
    {
        if ($this->file_type !== 'image') {
            return $this->snippetHtmlBasic();
        }

        $blur = MediaPublicUrl::forPath($this->blur_path);
        $full = MediaPublicUrl::forPath($this->webp_path ?? $this->optimized_path ?? $this->file_path);
        $alt = e($this->alt_text ?? '');

        if ($blur === '' || $full === '') {
            return $this->snippetHtmlBasic();
        }

        return '<img src="'.e($blur).'" data-src="'.e($full).'" loading="lazy" alt="'.$alt.'">';
    }
}
