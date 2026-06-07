<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceLocationPage extends Model
{
    protected $fillable = [
        'service_id',
        'pincode_id',
        'page_id',
        'slug',
        'location_slug',
        'city_slug',
        'quality_snapshot',
        'is_indexable',
    ];

    protected function casts(): array
    {
        return [
            'quality_snapshot' => 'array',
            'is_indexable' => 'boolean',
        ];
    }

    public function isPubliclyIndexable(): bool
    {
        $this->loadMissing(['service', 'page', 'pincode']);

        if (! $this->is_indexable) {
            return false;
        }

        if ($this->service === null || ! $this->service->isListedPublicly()) {
            return false;
        }

        if ($this->page === null || ! $this->page->is_active) {
            return false;
        }

        if (filled($this->page->robots_meta) && str_contains(strtolower((string) $this->page->robots_meta), 'noindex')) {
            return false;
        }

        return true;
    }

    public function publicUrl(): string
    {
        $this->loadMissing(['service', 'pincode']);

        return app(\App\Services\Operations\ServicePublicUrlBuilder::class)
            ->locationUrlForPin($this->service, $this->pincode, $this);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function pincode(): BelongsTo
    {
        return $this->belongsTo(PinCode::class, 'pincode_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
