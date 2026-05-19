<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
    'price_range',
    'featured_image',
    'icon',
    'detail_page_id',
    'gallery',
    'image_alt',
    'target_keywords',
    'ai_keywords',
    'quality_score',
    'is_active',
    'is_featured',
    'publish_status',
    'visibility',
    'sort_order',
])]
class Service extends Model
{
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

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'target_keywords' => 'array',
            'ai_keywords' => 'array',
            'quality_score' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'publish_status' => PublishStatus::class,
            'visibility' => ServiceVisibility::class,
            'sort_order' => 'integer',
            'detail_page_id' => 'integer',
        ];
    }

    public function seo(): HasOne
    {
        return $this->hasOne(ServiceSeo::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(ServiceFaq::class);
    }

    public function schema(): HasOne
    {
        return $this->hasOne(ServiceSchema::class);
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
            ->withTimestamps();
    }

    /**
     * Public canonical URL for this service (does not depend on whether a detail page is linked).
     */
    public function publicUrl(): string
    {
        return url('/services/'.$this->service_code);
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

        if (is_string($this->featured_image) && $this->featured_image !== '') {
            $node['image'] = $this->absoluteMediaUrl($this->featured_image);
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
            'name' => config('medca.brand_name', 'Medca Health Care'),
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

        return $this->faqs
            ->filter(fn (ServiceFaq $faq): bool => trim((string) $faq->question) !== '' && trim((string) $faq->answer) !== '')
            ->map(fn (ServiceFaq $faq): array => [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq->answer,
                ],
            ])
            ->values()
            ->all();
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

    private function absoluteMediaUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
