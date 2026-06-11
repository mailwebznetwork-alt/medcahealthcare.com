<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallTrackingNumber extends Model
{
    protected $fillable = [
        'provider',
        'exophone_sid',
        'phone_number',
        'phone_normalized',
        'label',
        'is_active',
        'is_primary',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function callEvents(): HasMany
    {
        return $this->hasMany(CallEvent::class);
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return substr($digits, 2);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return substr($digits, 1);
        }

        return $digits;
    }

    public static function findPrimaryActive(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();
    }
}
