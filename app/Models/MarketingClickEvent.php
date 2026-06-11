<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingClickEvent extends Model
{
    protected $fillable = [
        'event_type',
        'page_path',
        'page_title',
        'campaign',
        'source',
        'medium',
        'element_label',
        'destination_url',
        'device_type',
        'browser',
        'operating_system',
        'session_fingerprint',
        'marketing_attribution_session_id',
        'page_id',
        'service_id',
        'pin_code_id',
        'service_location_page_id',
        'lead_id',
        'meta',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function attributionSession(): BelongsTo
    {
        return $this->belongsTo(MarketingAttributionSession::class, 'marketing_attribution_session_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function pinCode(): BelongsTo
    {
        return $this->belongsTo(PinCode::class, 'pin_code_id');
    }

    public function serviceLocationPage(): BelongsTo
    {
        return $this->belongsTo(ServiceLocationPage::class);
    }
}
