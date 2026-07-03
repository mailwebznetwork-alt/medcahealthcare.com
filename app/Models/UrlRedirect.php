<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UrlRedirect extends Model
{
    protected $fillable = [
        'from_path',
        'to_url',
        'status_code',
        'is_active',
        'hit_count',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active' => 'boolean',
            'hit_count' => 'integer',
        ];
    }

    public static function normalizePath(string $path): string
    {
        $trimmed = '/'.trim($path, '/');

        return $trimmed === '/' ? '/' : rtrim($trimmed, '/');
    }
}
