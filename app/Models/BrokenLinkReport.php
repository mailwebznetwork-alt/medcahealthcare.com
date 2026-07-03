<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrokenLinkReport extends Model
{
    protected $fillable = [
        'status',
        'pages_scanned',
        'links_checked',
        'broken_count',
        'findings',
        'started_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'findings' => 'array',
            'pages_scanned' => 'integer',
            'links_checked' => 'integer',
            'broken_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
