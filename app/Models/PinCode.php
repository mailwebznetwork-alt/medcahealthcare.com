<?php

namespace App\Models;

use Database\Factories\PinCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable([
    'pincode',
    'area_name',
    'city',
    'locality',
    'is_serviceable',
    'is_active',
    'delivery_charge',
    'meta_title',
    'meta_description',
    'seo_keywords',
    'slug',
    'geo_page_ready',
])]
class PinCode extends Model
{
    /** @use HasFactory<PinCodeFactory> */
    use HasFactory;

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
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_pincodes')
            ->withTimestamps();
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
