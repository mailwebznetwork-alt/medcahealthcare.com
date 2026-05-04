<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingCampaign extends Model
{
    protected $fillable = [
        'name',
        'platform',
        'budget',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
        ];
    }
}
