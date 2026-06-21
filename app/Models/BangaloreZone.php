<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'slug',
    'description',
    'sort_order',
    'is_active',
])]
class IndiaZone extends Model
{
    protected $table = 'bangalore_zones';

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function pinCodes(): HasMany
    {
        return $this->hasMany(PinCode::class, 'bangalore_zone_id');
    }
}
