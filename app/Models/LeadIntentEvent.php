<?php

namespace App\Models;

use App\Enums\LeadAttributionBucket;
use App\Enums\LeadIntentChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadIntentEvent extends Model
{
    protected $fillable = [
        'intent_type',
        'channel',
        'attribution_bucket',
        'source',
        'medium',
        'campaign',
        'landing_page',
        'service_page',
        'marketing_attribution_session_id',
        'page_id',
        'service_id',
        'pin_code_id',
        'service_location_page_id',
        'lead_id',
        'marketing_click_event_id',
        'meta',
        'session_fingerprint',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => LeadIntentChannel::class,
            'attribution_bucket' => LeadAttributionBucket::class,
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function marketingClickEvent(): BelongsTo
    {
        return $this->belongsTo(MarketingClickEvent::class);
    }

    public function attributionSession(): BelongsTo
    {
        return $this->belongsTo(MarketingAttributionSession::class, 'marketing_attribution_session_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
