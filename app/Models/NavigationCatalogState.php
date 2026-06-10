<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationCatalogState extends Model
{
    protected $fillable = [
        'zone',
        'exclusions',
        'manual_children',
        'sibling_orders',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exclusions' => 'array',
            'manual_children' => 'array',
            'sibling_orders' => 'array',
        ];
    }
}
