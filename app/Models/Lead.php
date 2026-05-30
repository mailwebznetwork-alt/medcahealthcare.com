<?php

namespace App\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Lead extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'phone',
        'email',
        'service',
        'message',
        'ai_priority_score',
        'ai_intent_category',
        'source',
        'campaign',
        'pin_code_id',
        'status',
        'assigned_to',
        'follow_up_date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Lead $lead): void {
            if ($lead->uuid === null || $lead->uuid === '') {
                $lead->uuid = (string) Str::uuid();
            }
        });

        static::saving(function (Lead $lead): void {
            $n = self::normalizePhone($lead->phone);
            $lead->phone_normalized = $n !== '' ? $n : $lead->phone;
        });
    }

    protected function casts(): array
    {
        return [
            'source' => LeadSource::class,
            'status' => LeadStatus::class,
            'follow_up_date' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class)->orderByDesc('created_at');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function pinCode(): BelongsTo
    {
        return $this->belongsTo(PinCode::class);
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
