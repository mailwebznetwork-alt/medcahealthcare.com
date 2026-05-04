<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingCommunicationSnapshot extends Model
{
    protected $fillable = [
        'channel',
        'period_start',
        'period_end',
        'metrics',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'metrics' => 'array',
        ];
    }
}
