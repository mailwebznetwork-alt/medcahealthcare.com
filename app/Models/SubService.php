<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Concerns\HasCatalogMasterFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'service_id',
    'standalone_service_id',
    'page_id',
    'sub_service_code',
    'title',
    'short_summary',
    'description',
    'sort_order',
    'is_active',
    'is_featured',
    'publish_status',
    'visibility',
    'avg_rating_cache',
    'is_top_rated',
    'show_on_homepage',
    'show_on_about',
    'show_on_contact',
    'internal_links_snapshot',
    'custom_fields',
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
    'gallery',
    'gallery_media_ids',
    'gallery_meta',
    'image_alt',
    'featured_image_meta',
    'trust_signals',
    'optimization_snapshot',
    'target_keywords',
    'ai_keywords',
    'quality_score',
])]
class SubService extends Model
{
    use HasCatalogMasterFields;

    protected function casts(): array
    {
        return array_merge([
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'publish_status' => PublishStatus::class,
            'visibility' => ServiceVisibility::class,
            'avg_rating_cache' => 'decimal:1',
            'is_top_rated' => 'boolean',
            'show_on_homepage' => 'boolean',
            'show_on_about' => 'boolean',
            'show_on_contact' => 'boolean',
            'page_id' => 'integer',
            'internal_links_snapshot' => 'array',
        ], $this->catalogMasterFieldCasts());
    }

    public static function findByCode(int $serviceId, string $code): ?self
    {
        return static::query()
            ->where('service_id', $serviceId)
            ->where('sub_service_code', trim($code))
            ->first();
    }

    /**
     * @param  Builder<SubService>  $query
     * @return Builder<SubService>
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

    /**
     * @param  Builder<SubService>  $query
     * @return Builder<SubService>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    public function isListedPublicly(): bool
    {
        return $this->is_active
            && $this->publish_status === PublishStatus::Published
            && $this->visibility === ServiceVisibility::Public;
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return HasMany<SubServicePincodeExclusion, $this>
     */
    public function pincodeExclusions(): HasMany
    {
        return $this->hasMany(SubServicePincodeExclusion::class);
    }

    /**
     * Parent service pincodes not excluded for this sub-service.
     *
     * @return list<int>
     */
    public function includedPincodeIds(): array
    {
        $this->loadMissing(['service.pincodes', 'pincodeExclusions']);

        if ($this->service === null) {
            return [];
        }

        $excluded = $this->pincodeExclusions->pluck('pincode_id')->map(fn ($id) => (int) $id)->all();

        return $this->service->pincodes
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id): bool => in_array($id, $excluded, true))
            ->values()
            ->all();
    }

    /**
     * When promoted to a full standalone service.
     */
    public function standaloneService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'standalone_service_id');
    }

    public function seo(): HasOne
    {
        return $this->hasOne(SubServiceSeo::class);
    }

    public function schema(): HasOne
    {
        return $this->hasOne(SubServiceSchema::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(SubServiceFaq::class)->orderBy('sort_order');
    }

    public function linkedPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function publicUrl(): string
    {
        $this->loadMissing('service');

        return route('public.services.sub', [
            'code' => $this->service->service_code,
            'subCode' => $this->sub_service_code,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchemaFragment(string $parentUrl): array
    {
        return array_filter([
            '@type' => 'Service',
            '@id' => $parentUrl.'#sub-'.$this->sub_service_code,
            'name' => $this->title,
            'description' => $this->short_summary ?: $this->description,
            'url' => $parentUrl.'#'.$this->sub_service_code,
        ]);
    }
}
