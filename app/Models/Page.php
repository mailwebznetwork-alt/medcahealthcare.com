<?php

namespace App\Models;

use App\Enums\PageCategory;
use App\Enums\PageLayoutMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'keywords',
        'focus_keywords',
        'canonical_url',
        'robots_meta',
        'page_category',
        'page_source',
        'registry_owner',
        'visibility_flags',
        'og_image',
        'og_image_alt',
        'og_title',
        'og_description',
        'twitter_card',
        'hreflang_json',
        'entity_tags',
        'fact_check_verified',
        'content_reviewed_at',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'heading_h2',
        'heading_h3',
        'aeo_question',
        'aeo_answer',
        'ai_context',
        'search_intent',
        'schema_json',
        'schema_type',
        'gtm_code',
        'pixel_code',
        'is_active',
        'layout_mode',
        'block_overrides_json',
        'deployment_meta_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
            'focus_keywords' => 'array',
            'heading_h2' => 'array',
            'heading_h3' => 'array',
            'hreflang_json' => 'array',
            'entity_tags' => 'array',
            'fact_check_verified' => 'boolean',
            'is_active' => 'boolean',
            'page_category' => PageCategory::class,
            'visibility_flags' => 'array',
            'layout_mode' => PageLayoutMode::class,
            'block_overrides_json' => 'array',
            'deployment_meta_json' => 'array',
            'content_reviewed_at' => 'datetime',
        ];
    }

    public static function publicPathForSlug(string $slug): string
    {
        if ($slug === 'home') {
            return '/';
        }

        if (in_array($slug, config('public_pages.root_slugs', []), true)) {
            return '/'.$slug;
        }

        return '/p/'.$slug;
    }

    public function publicPath(): string
    {
        return self::publicPathForSlug((string) $this->slug);
    }

    public function publicUrl(): string
    {
        return url($this->publicPath());
    }

    public function usesCanvasLayout(): bool
    {
        return $this->layout_mode === PageLayoutMode::Canvas;
    }

    public static function usesRootPublicPath(string $slug): bool
    {
        return $slug === 'home' || in_array($slug, config('public_pages.root_slugs', []), true);
    }

    protected static function booted(): void
    {
        static::creating(function (Page $page): void {
            if (empty($page->uuid)) {
                $page->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Ordered tokens from page content: {{block:x}} / {{module:y}} lines.
     *
     * @return list<array{type: string, slug: string}>
     */
    public static function parseContentTokens(?string $content): array
    {
        if ($content === null || trim($content) === '') {
            return [];
        }

        preg_match_all('/\{\{\s*(block|module|section)\s*:\s*([^}]+?)\s*\}\}/', $content, $matches, PREG_SET_ORDER);
        $parts = [];
        foreach ($matches as $row) {
            $parts[] = [
                'type' => strtolower(trim($row[1])),
                'slug' => trim($row[2]),
            ];
        }

        return $parts;
    }

    /**
     * @param  list<array{type: string, slug: string}>  $parts
     */
    public static function buildContentFromParts(array $parts): string
    {
        $lines = [];
        foreach ($parts as $part) {
            $type = $part['type'];
            $slug = $part['slug'];
            $lines[] = '{{'.$type.':'.$slug.'}}';
        }

        return implode("\n", $lines);
    }

    /**
     * Snapshot for revision restore (structured fields + PIN pivot).
     *
     * @return array<string, mixed>
     */
    public function toRevisionSnapshot(): array
    {
        $this->loadMissing('pinCodes');

        $attributes = $this->only([
            'uuid',
            'title',
            'slug',
            'content',
            'meta_title',
            'meta_description',
            'keywords',
            'focus_keywords',
            'canonical_url',
            'robots_meta',
            'og_image',
            'og_image_alt',
            'hreflang_json',
            'entity_tags',
            'fact_check_verified',
            'content_reviewed_at',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'heading_h2',
            'heading_h3',
            'aeo_question',
            'aeo_answer',
            'ai_context',
            'search_intent',
            'schema_json',
            'schema_type',
            'gtm_code',
            'pixel_code',
            'is_active',
            'layout_mode',
        ]);

        $attributes['content_reviewed_at'] = $this->content_reviewed_at?->toAtomString();

        $this->loadMissing('faqs');

        $attributes['faqs'] = $this->faqs->map(fn (PageFaq $faq) => [
            'question' => $faq->question,
            'answer' => $faq->answer,
            'sort_order' => $faq->sort_order,
        ])->values()->all();

        $attributes['pin_codes'] = $this->pinCodes->map(fn ($pc) => [
            'id' => $pc->id,
            'serviceability' => (bool) $pc->pivot->serviceability,
            'delivery_charge' => $pc->pivot->delivery_charge,
            'location_keywords' => $pc->pivot->location_keywords,
        ])->values()->all();

        return $attributes;
    }

    /**
     * @return BelongsToMany<PinCode, $this>
     */
    public function pinCodes(): BelongsToMany
    {
        return $this->belongsToMany(PinCode::class, 'page_pin_codes')
            ->withPivot(['serviceability', 'delivery_charge', 'location_keywords'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<PageRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class);
    }

    /**
     * @return HasMany<PageFaq, $this>
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(PageFaq::class)->orderBy('sort_order');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
