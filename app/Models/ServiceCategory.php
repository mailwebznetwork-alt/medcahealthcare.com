<?php

namespace App\Models;

use Database\Factories\ServiceCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'code',
    'description',
    'parent_id',
    'sort_order',
    'is_active',
])]
class ServiceCategory extends Model
{
    /** @use HasFactory<ServiceCategoryFactory> */
    use HasFactory;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (ServiceCategory $category): void {
            $category->code = self::normalizeCode((string) $category->code);
        });
    }

    public static function normalizeCode(string $code): string
    {
        $code = strtolower(trim($code));
        $code = str_replace([' ', '_'], '-', $code);

        return $code;
    }

    public static function findActiveByCode(string $code): ?self
    {
        $code = self::normalizeCode($code);
        if ($code === '') {
            return null;
        }

        return static::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'parent_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<ServiceCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_category_map', 'service_category_id', 'service_id')
            ->withTimestamps();
    }

    /**
     * @param  Builder<ServiceCategory>  $query
     * @return Builder<ServiceCategory>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<ServiceCategory>  $query
     * @return Builder<ServiceCategory>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @param  Builder<ServiceCategory>  $query
     * @return Builder<ServiceCategory>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function publicUrl(): string
    {
        return url('/service-categories/'.$this->code);
    }

    public function breadcrumbLabel(): string
    {
        if ($this->parent_id === null) {
            return $this->name;
        }

        $this->loadMissing('parent');

        return $this->parent
            ? $this->parent->name.' › '.$this->name
            : $this->name;
    }

    public function makeUniqueCode(string $base): string
    {
        $slug = $base !== '' ? $base : 'category';
        $original = $slug;
        $n = 1;
        while (static::query()
            ->where('code', $slug)
            ->when($this->exists, fn (Builder $q) => $q->where('id', '!=', $this->id))
            ->exists()) {
            $slug = $original.'-'.$n;
            $n++;
        }

        return $slug;
    }

    public function suggestCodeFromName(): string
    {
        return $this->makeUniqueCode(Str::slug($this->name));
    }
}
