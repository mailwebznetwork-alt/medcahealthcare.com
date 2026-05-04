<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MarketingEmailTracker extends Model
{
    protected $fillable = [
        'token',
        'label',
        'open_count',
    ];

    protected function casts(): array
    {
        return [
            'open_count' => 'integer',
        ];
    }

    public static function createWithToken(?string $label = null): self
    {
        return self::query()->create([
            'token' => (string) Str::uuid(),
            'label' => $label,
            'open_count' => 0,
        ]);
    }

    public function pixelUrl(): string
    {
        return route('marketing.email-open-pixel', ['token' => $this->token]);
    }
}
