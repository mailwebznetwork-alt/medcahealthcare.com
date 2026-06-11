<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ServicePincode extends Pivot
{
    protected $table = 'service_pincodes';

    public const SOURCE_CATEGORY = 'category';

    public const SOURCE_MANUAL = 'manual';

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
            'category_filter_ids' => 'array',
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }
}
