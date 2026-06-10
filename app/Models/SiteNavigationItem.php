<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteNavigationItem extends Model
{
    /** @use HasFactory<\Database\Factories\SiteNavigationItemFactory> */
    use HasFactory;

    public const string ZONE_HEADER = 'header';

    public const string ZONE_FOOTER = 'footer';

    public const string TYPE_PAGE = 'page';

    public const string TYPE_CATEGORY = 'category';

    public const string TYPE_SERVICE = 'service';

    public const string TYPE_SUB_SERVICE = 'sub_service';

    public const string TYPE_URL = 'url';

    public const string TYPE_GROUP = 'group';

    protected $fillable = [
        'zone',
        'parent_id',
        'item_type',
        'page_id',
        'service_category_id',
        'service_id',
        'sub_service_id',
        'custom_url',
        'title',
        'sort_order',
        'custom_label',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'parent_id' => 'integer',
            'page_id' => 'integer',
            'service_category_id' => 'integer',
            'service_id' => 'integer',
            'sub_service_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SiteNavigationItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<SiteNavigationItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<SubService, $this>
     */
    public function subService(): BelongsTo
    {
        return $this->belongsTo(SubService::class);
    }
}
