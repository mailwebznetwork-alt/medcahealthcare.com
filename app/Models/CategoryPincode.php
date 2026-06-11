<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryPincode extends Pivot
{
    protected $table = 'category_pincodes';

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_visible' => 'boolean',
        ];
    }
}
