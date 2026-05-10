<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteNavigationItem extends Model
{
    /** @use HasFactory<\Database\Factories\SiteNavigationItemFactory> */
    use HasFactory;

    public const string ZONE_HEADER = 'header';

    public const string ZONE_FOOTER = 'footer';

    protected $fillable = [
        'zone',
        'page_id',
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
        ];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
