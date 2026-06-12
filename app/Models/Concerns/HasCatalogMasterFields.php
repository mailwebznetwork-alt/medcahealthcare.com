<?php

namespace App\Models\Concerns;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasCatalogMasterFields
{
    public function getTitleAttribute(): ?string
    {
        if (array_key_exists('title', $this->attributes) && $this->attributes['title'] !== null) {
            return (string) $this->attributes['title'];
        }

        return isset($this->attributes['name']) ? (string) $this->attributes['name'] : null;
    }

    public function getServiceCodeAttribute(): ?string
    {
        if (array_key_exists('service_code', $this->attributes) && $this->attributes['service_code'] !== null) {
            return (string) $this->attributes['service_code'];
        }

        if (array_key_exists('sub_service_code', $this->attributes) && $this->attributes['sub_service_code'] !== null) {
            return (string) $this->attributes['sub_service_code'];
        }

        return isset($this->attributes['code']) ? (string) $this->attributes['code'] : null;
    }

    public function getDetailPageIdAttribute(): ?int
    {
        if (array_key_exists('detail_page_id', $this->attributes)) {
            $value = $this->attributes['detail_page_id'];

            return $value !== null ? (int) $value : null;
        }

        $pageId = $this->attributes['page_id'] ?? null;

        return $pageId !== null ? (int) $pageId : null;
    }

    public function setDetailPageIdAttribute(mixed $value): void
    {
        if (array_key_exists('detail_page_id', $this->attributes) || $this->isFillable('detail_page_id')) {
            $this->attributes['detail_page_id'] = $value;

            return;
        }

        $this->attributes['page_id'] = $value;
    }

    public function listingLines(string $attribute): string
    {
        $items = $this->{$attribute};

        if (! is_array($items)) {
            return '';
        }

        return implode("\n", \App\Services\Import\ImportSupport::normalizeLineArray($items));
    }

    public function publicListingTitle(): string
    {
        return (string) ($this->title ?? '');
    }

    /**
     * @return array<string, string>
     */
    protected function catalogMasterFieldCasts(): array
    {
        return [
            'key_benefits' => 'array',
            'eligibility' => 'array',
            'process_steps' => 'array',
            'procedures' => 'array',
            'specialized_care' => 'array',
            'shifts' => 'array',
            'gallery' => 'array',
            'gallery_media_ids' => 'array',
            'gallery_meta' => 'array',
            'featured_image_meta' => 'array',
            'trust_signals' => 'array',
            'optimization_snapshot' => 'array',
            'target_keywords' => 'array',
            'ai_keywords' => 'array',
            'custom_fields' => 'array',
            'quality_score' => 'integer',
            'featured_media_id' => 'integer',
            'icon_media_id' => 'integer',
            'publish_status' => PublishStatus::class,
        ];
    }

    /**
     * Empty relations for catalog forms that reuse service tab partials.
     *
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    public function getCatalogReviewsRelation()
    {
        if (method_exists($this, 'reviews')) {
            return $this->reviews();
        }

        return collect();
    }

    /**
     * @return HasMany<\App\Models\SubService, $this>|\Illuminate\Support\Collection<int, mixed>
     */
    public function getCatalogChildItemsRelation()
    {
        if (method_exists($this, 'subServices')) {
            return $this->subServices();
        }

        if (method_exists($this, 'services')) {
            return $this->services();
        }

        return collect();
    }
}
