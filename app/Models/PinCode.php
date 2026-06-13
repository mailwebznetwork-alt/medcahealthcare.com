<?php

namespace App\Models;

use Database\Factories\PinCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'pincode',
    'area_name',
    'city',
    'state',
    'locality',
    'coverage_text',
    'emergency_coverage_text',
    'is_serviceable',
    'is_active',
    'delivery_charge',
    'meta_title',
    'meta_description',
    'seo_keywords',
    'slug',
    'geo_page_ready',
    'geo_location_id',
    'business_profile_id',
    'landing_page',
    'priority',
    'custom_fields',
    'bangalore_zone_id',
])]
class PinCode extends Model
{
    /** @use HasFactory<PinCodeFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * Active, serviceable pins eligible for coverage sync (excludes soft-deleted).
     *
     * @return Builder<self>
     */
    public static function eligibleForCoverage(): Builder
    {
        return static::query()
            ->where('is_active', true)
            ->where('is_serviceable', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_serviceable' => 'boolean',
            'is_active' => 'boolean',
            'geo_page_ready' => 'boolean',
            'delivery_charge' => 'decimal:2',
            'custom_fields' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PinCode $pinCode): void {
            if ($pinCode->slug === null || $pinCode->slug === '') {
                $pinCode->slug = $pinCode->makeUniqueSlug(
                    Str::slug($pinCode->area_name.'-'.$pinCode->pincode)
                );
            }
        });
    }

    public function makeUniqueSlug(string $base): string
    {
        $slug = $base !== '' ? $base : 'pin-'.$this->pincode;
        $original = $slug;
        $n = 1;
        while (static::query()
            ->where('slug', $slug)
            ->when($this->exists, fn ($q) => $q->where('id', '!=', $this->id))
            ->exists()) {
            $slug = $original.'-'.$n;
            $n++;
        }

        return $slug;
    }

    /**
     * Growth GEO forms use "serviceable"; Operations uses is_serviceable.
     */
    protected function serviceable(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => (bool) $this->is_serviceable,
            set: fn (mixed $value): array => ['is_serviceable' => (bool) $value],
        );
    }

    /**
     * @return BelongsTo<GeoLocation, $this>
     */
    public function geoLocation(): BelongsTo
    {
        return $this->belongsTo(GeoLocation::class);
    }

    /**
     * @return BelongsTo<BangaloreZone, $this>
     */
    public function bangaloreZone(): BelongsTo
    {
        return $this->belongsTo(BangaloreZone::class);
    }

    /**
     * @return BelongsTo<BusinessProfile, $this>
     */
    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function landmarks(): HasMany
    {
        return $this->hasMany(PinCodeLandmark::class, 'pincode_id')->orderBy('sort_order');
    }

    public function hospitals(): HasMany
    {
        return $this->hasMany(PinCodeHospital::class, 'pincode_id')->orderBy('sort_order');
    }

    public function locationFaqs(): HasMany
    {
        return $this->hasMany(PinCodeLocationFaq::class, 'pincode_id')->orderBy('sort_order');
    }

    public function nearbyAreas(): HasMany
    {
        return $this->hasMany(PinCodeNearbyArea::class, 'pincode_id')->orderBy('sort_order');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_pincodes', 'pincode_id', 'service_id')
            ->using(ServicePincode::class)
            ->withPivot([
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
     * @return BelongsToMany<Page, $this>
     */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_pin_codes')
            ->withPivot(['serviceability', 'delivery_charge', 'location_keywords'])
            ->withTimestamps();
    }
}
