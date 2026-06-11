<?php

namespace App\Models;

use App\Enums\LeadPipelineStage;
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
        'lead_source',
        'lead_medium',
        'lead_campaign',
        'lead_content',
        'lead_term',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'gclid',
        'fbclid',
        'landing_page',
        'referrer_url',
        'first_touch_source',
        'first_touch_medium',
        'first_touch_campaign',
        'first_touch_at',
        'last_touch_source',
        'last_touch_medium',
        'last_touch_campaign',
        'device_type',
        'browser',
        'operating_system',
        'country',
        'region',
        'city',
        'pin_code_id',
        'marketing_attribution_session_id',
        'page_id',
        'service_id',
        'service_location_page_id',
        'status',
        'pipeline_stage',
        'pipeline_stage_changed_at',
        'converted_at',
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
            'pipeline_stage' => LeadPipelineStage::class,
            'first_touch_at' => 'datetime',
            'pipeline_stage_changed_at' => 'datetime',
            'converted_at' => 'datetime',
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
        return $this->belongsTo(PinCode::class, 'pin_code_id');
    }

    public function attributionSession(): BelongsTo
    {
        return $this->belongsTo(MarketingAttributionSession::class, 'marketing_attribution_session_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function serviceRelation(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function serviceLocationPage(): BelongsTo
    {
        return $this->belongsTo(ServiceLocationPage::class);
    }

    public function pipelineHistories(): HasMany
    {
        return $this->hasMany(LeadPipelineStageHistory::class)->orderByDesc('changed_at');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderByDesc('occurred_at');
    }

    public function conversionEvents(): HasMany
    {
        return $this->hasMany(MarketingConversionEvent::class)->orderByDesc('converted_at');
    }

    public function clickEvents(): HasMany
    {
        return $this->hasMany(MarketingClickEvent::class)->orderByDesc('occurred_at');
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
