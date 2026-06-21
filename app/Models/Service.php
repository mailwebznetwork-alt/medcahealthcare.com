<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Review;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'title',
    'service_code',
    'short_summary',
    'description',
    'key_benefits',
    'eligibility',
    'process_steps',
    'ai_summary',
    'procedures',
    'specialized_care',
    'shifts',
    'price_range',
    'featured_image',
    'featured_media_id',
    'icon',
    'icon_media_id',
    'line_icon',
    'detail_page_id',
    'gallery',
    'gallery_media_ids',
    'gallery_meta',
    'trust_signals',
    'optimization_snapshot',
    'internal_links_snapshot',
    'image_alt',
    'featured_image_meta',
    'target_keywords',
    'ai_keywords',
    'quality_score',
    'is_active',
    'is_featured',
    'is_top_rated',
    'avg_rating_cache',
    'show_on_homepage',
    'show_on_about',
    'show_on_contact',
    'publish_status',
    'visibility',
    'sort_order',
    'custom_fields',
    'quick_answer',
    'why_medca',
    'key_takeaways',
    'activities_included',
    'medical_review_status',
    'reviewed_by',
    'reviewed_at',
    'verification_status',
    'featured_video_url',
    'featured_video_title',
    'featured_video_description',
])]
class Service extends Model
{
    use \App\Models\Concerns\HasMasterSpecContentFields;
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    /**
     * Block Factory / dynamic blocks resolve by stable code, never by slug.
     */
    public static function findByCode(string $code): ?self
    {
        return static::query()->where('service_code', $code)->first();
    }

    /**
     * Public routes, sitemap, and Schema.org: published, active, public only.
     */
    public static function findPublishedByCode(string $code): ?self
    {
        return static::query()
            ->where('service_code', $code)
            ->where('is_active', true)
            ->where('publish_status', PublishStatus::Published)
            ->where('visibility', ServiceVisibility::Public)
            ->first();
    }

    /**
     * Block {{service:CODE}} binding: any active service (draft/private allowed for staging).
     */
    public static function findForBlockBinding(string $code): ?self
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        return static::query()
            ->where('service_code', $code)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Public detail URL (/services/CODE): active, public visibility (draft allowed until published).
     */
    public static function findPubliclyViewableByCode(string $code): ?self
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        return static::query()
            ->where('service_code', $code)
            ->where('is_active', true)
            ->where('visibility', ServiceVisibility::Public)
            ->first();
    }

    /**
     * Listed in sitemap, Schema.org head output, and canonical indexable URLs.
     */
    public function isListedPublicly(): bool
    {
        return $this->is_active
            && $this->publish_status === PublishStatus::Published
            && $this->visibility === ServiceVisibility::Public;
    }

    public function publicListingTitle(): string
    {
        return (string) ($this->title ?? '');
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopePublicListing(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('publish_status', PublishStatus::Published)
            ->where('visibility', ServiceVisibility::Public)
            ->orderBy('sort_order')
            ->orderBy('title');
    }

    protected function casts(): array
    {
        return array_merge([
            'gallery' => 'array',
            'gallery_media_ids' => 'array',
            'gallery_meta' => 'array',
            'featured_media_id' => 'integer',
            'icon_media_id' => 'integer',
            'trust_signals' => 'array',
            'optimization_snapshot' => 'array',
            'featured_image_meta' => 'array',
            'internal_links_snapshot' => 'array',
            'key_benefits' => 'array',
            'eligibility' => 'array',
            'process_steps' => 'array',
            'procedures' => 'array',
            'specialized_care' => 'array',
            'shifts' => 'array',
            'target_keywords' => 'array',
            'ai_keywords' => 'array',
            'quality_score' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_top_rated' => 'boolean',
            'avg_rating_cache' => 'decimal:1',
            'show_on_homepage' => 'boolean',
            'show_on_about' => 'boolean',
            'show_on_contact' => 'boolean',
            'publish_status' => PublishStatus::class,
            'visibility' => ServiceVisibility::class,
            'sort_order' => 'integer',
            'detail_page_id' => 'integer',
            'custom_fields' => 'array',
        ], $this->masterSpecContentFieldCasts());
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function iconMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'icon_media_id');
    }

    public function seo(): HasOne
    {
        return $this->hasOne(ServiceSeo::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(ServiceFaq::class);
    }

    /**
     * Child offerings under this service (e.g. Medical Lab → Blood Test, ECG).
     *
     * @return HasMany<SubService, $this>
     */
    public function subServices(): HasMany
    {
        return $this->hasMany(SubService::class)->orderBy('sort_order')->orderBy('title');
    }

    /**
     * Sub-services promoted to standalone service records.
     *
     * @return HasMany<SubService, $this>
     */
    public function promotedSubServices(): HasMany
    {
        return $this->hasMany(SubService::class, 'standalone_service_id');
    }

    public function schema(): HasOne
    {
        return $this->hasOne(ServiceSchema::class);
    }

    /**
     * @return HasMany<ServiceLocationPage, $this>
     */
    public function locationPages(): HasMany
    {
        return $this->hasMany(ServiceLocationPage::class);
    }

    /**
     * Optional admin-designed detail layout (Site Architect → Pages); null falls back to default.
     *
     * @return BelongsTo<Page, $this>
     */
    public function detailPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'detail_page_id');
    }

    /**
     * GEO coverage — existing pin_codes rows only (no free-text pincodes).
     *
     * @return BelongsToMany<PinCode, $this>
     */
    public function pincodes(): BelongsToMany
    {
        return $this->belongsToMany(PinCode::class, 'service_pincodes', 'service_id', 'pincode_id')
            ->using(ServicePincode::class)
            ->withPivot([
                'pin_source',
                'priority',
                'is_visible',
                'is_featured',
                'coverage_notes',
                'category_filter_ids',
                'effective_from',
                'effective_until',
            ])
            ->withTimestamps()
            ->orderByPivot('priority', 'desc');
    }

    /**
     * Organizational taxonomy — not an SEO entity; service SEO remains canonical.
     *
     * @return BelongsToMany<ServiceCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCategory::class, 'service_category_map', 'service_id', 'service_category_id')
            ->withPivot(['is_primary'])
            ->withTimestamps();
    }

    public function primaryCategory(): ?ServiceCategory
    {
        $this->loadMissing('categories');

        $primary = $this->categories->firstWhere(fn (ServiceCategory $category): bool => (bool) $category->pivot?->is_primary);

        return $primary ?? $this->categories->first();
    }

    /**
     * @param  Builder<Service>  $query
     * @param  list<int|string>  $categoryIds
     * @return Builder<Service>
     */
    public function scopeInCategories(Builder $query, array $categoryIds): Builder
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): int => (int) $id,
            $categoryIds
        ), static fn (int $id): bool => $id > 0)));

        if ($ids === []) {
            return $query;
        }

        return $query->whereHas('categories', function (Builder $categoryQuery) use ($ids): void {
            $categoryQuery->whereIn('service_categories.id', $ids);
        });
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', Review::STATUS_APPROVED);
    }

    public function averageApprovedRating(): ?float
    {
        $avg = $this->approvedReviews()->avg('rating');

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    public function approvedReviewsCount(): int
    {
        return (int) $this->approvedReviews()->count();
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeForPincode(Builder $query, string $pincode): Builder
    {
        $normalized = preg_replace('/\D/', '', $pincode) ?? '';

        return $query->whereHas('pincodes', function (Builder $pinQuery) use ($normalized): void {
            $pinQuery
                ->where('pincode', $normalized)
                ->where('is_active', true)
                ->where(function (Builder $pivotQuery): void {
                    $pivotQuery
                        ->where('service_pincodes.is_visible', true)
                        ->orWhereNull('service_pincodes.is_visible');
                });
        });
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeLocalizedListing(Builder $query, ?string $pincode): Builder
    {
        $query->publicListing();

        if ($pincode !== null && $pincode !== '') {
            $query->forPincode($pincode);
        }

        return $query;
    }

    public function isAvailableInPincode(?string $pincode): bool
    {
        if ($pincode === null || $pincode === '') {
            return true;
        }

        $normalized = preg_replace('/\D/', '', $pincode) ?? '';

        return $this->pincodes()
            ->where('pincode', $normalized)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Public canonical URL for this service (does not depend on whether a detail page is linked).
     */
    public function publicUrl(): string
    {
        return app(\App\Services\Operations\ServicePublicUrlBuilder::class)->serviceUrl($this);
    }

    public function publicCanonicalUrl(): string
    {
        return $this->seo?->canonical_url ?: $this->publicUrl();
    }

    /**
     * Schema.org "Service" graph node — emitted into <head> regardless of visual rendering.
     *
     * @return array<string, mixed>
     */
    public function toServiceSchema(): array
    {
        $this->loadMissing(['seo', 'pincodes']);

        $node = [
            '@type' => 'Service',
            'name' => $this->title,
            'serviceType' => $this->title,
            'url' => $this->publicUrl(),
        ];

        $description = $this->seo?->meta_description
            ?: $this->short_summary
            ?: (is_string($this->description) ? trim(strip_tags($this->description)) : null);
        if ($description !== null && $description !== '') {
            $node['description'] = $description;
        }

        $imagePath = $this->featured_image;
        if ($this->relationLoaded('featuredMedia') && $this->featuredMedia) {
            $imagePath = $this->featuredMedia->referencePath();
        } elseif ($this->featured_media_id && ! $this->relationLoaded('featuredMedia')) {
            $this->loadMissing('featuredMedia');
            if ($this->featuredMedia) {
                $imagePath = $this->featuredMedia->referencePath();
            }
        }
        if (is_string($imagePath) && $imagePath !== '') {
            $node['image'] = $this->absoluteMediaUrl($imagePath);
        }

        if (is_string($this->price_range) && $this->price_range !== '') {
            $node['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => 'INR',
                'priceSpecification' => [
                    '@type' => 'PriceSpecification',
                    'priceCurrency' => 'INR',
                    'price' => $this->price_range,
                ],
            ];
        }

        $areaServed = $this->pincodes
            ->map(fn (PinCode $pc): array => array_filter([
                '@type' => 'PostalAddress',
                'postalCode' => $pc->pincode,
                'addressLocality' => $pc->area_name ?: $pc->locality ?: null,
                'addressRegion' => $pc->city ?: null,
                'addressCountry' => 'IN',
            ], fn ($v) => $v !== null && $v !== ''))
            ->values()
            ->all();

        if ($areaServed !== []) {
            $node['areaServed'] = $areaServed;
        }

        $node['provider'] = [
            '@type' => 'LocalBusiness',
            'name' => config('medca.brand_name', 'Karnataka Diagnostic Centre'),
            'telephone' => config('medca.phone_tel'),
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => config('medca.location_display'),
                'addressCountry' => 'IN',
            ],
        ];

        return $node;
    }

    /**
     * FAQPage entities derived from this service's `service_faqs` rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toFaqEntities(): array
    {
        $this->loadMissing('faqs');

        return array_map(
            fn (array $faq): array => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ],
            \App\Support\FaqPairNormalizer::expandMany($this->faqs)
        );
    }

    public function hasPriceRange(): bool
    {
        return is_string($this->price_range) && trim($this->price_range) !== '';
    }

    /**
     * Stable Blade variable name for {{service:CODE}} tokens — hyphens become underscores.
     */
    public function bladeVariableName(): string
    {
        return str_replace('-', '_', (string) $this->service_code);
    }

    /**
     * @param  'procedures'|'specialized_care'|'shifts'  $attribute
     */
    public function listingLines(string $attribute): string
    {
        $items = $this->{$attribute};

        if (! is_array($items)) {
            return '';
        }

        return implode("\n", array_map(static fn (mixed $line): string => (string) $line, $items));
    }

    private function absoluteMediaUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
