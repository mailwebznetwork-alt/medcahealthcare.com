<?php

namespace App\Models;

use App\Enums\CallEventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallEvent extends Model
{
    protected $fillable = [
        'provider',
        'provider_call_sid',
        'idempotency_key',
        'provider_event_type',
        'status',
        'raw_status',
        'direction',
        'caller_number',
        'caller_normalized',
        'called_number',
        'call_tracking_number_id',
        'duration_seconds',
        'started_at',
        'ended_at',
        'recording_url',
        'custom_field',
        'raw_payload',
        'marketing_attribution_session_id',
        'marketing_click_event_id',
        'lead_id',
        'page_id',
        'service_id',
        'pin_code_id',
        'service_location_page_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CallEventStatus::class,
            'raw_payload' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'occurred_at' => 'datetime',
        ];
    }

    public function trackingNumber(): BelongsTo
    {
        return $this->belongsTo(CallTrackingNumber::class, 'call_tracking_number_id');
    }

    public function attributionSession(): BelongsTo
    {
        return $this->belongsTo(MarketingAttributionSession::class, 'marketing_attribution_session_id');
    }

    public function marketingClickEvent(): BelongsTo
    {
        return $this->belongsTo(MarketingClickEvent::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function pinCode(): BelongsTo
    {
        return $this->belongsTo(PinCode::class, 'pin_code_id');
    }
}
