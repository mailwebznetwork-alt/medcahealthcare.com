<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class Integration extends Model
{
    protected $fillable = [
        'name',
        'type',
        'credentials',
        'is_enabled',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'array',
            'is_enabled' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_enabled', true);
    }

    public function getCredential(string $key): mixed
    {
        $credentials = is_array($this->credentials) ? $this->credentials : [];
        $value = $credentials[$key] ?? null;

        if (! is_string($value) || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value;
        }

    }

    public function accounts(): HasMany
    {
        return $this->hasMany(IntegrationAccount::class);
    }
}
