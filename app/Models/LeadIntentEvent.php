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
}
