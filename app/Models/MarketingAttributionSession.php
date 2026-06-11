<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingAttributionSession extends Model
{
    protected $fillable = [
        'session_fingerprint',
        'laravel_session_id',
        'landing_page_path',
        'page_id',
        'service_id',
        'pin_code_id',
        'service_location_page_id',
        'service_category_id',
        'sub_service_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
        'referrer',
        'first_touch_json',
        'last_touch_json',
        'converted_lead_id',
        'first_seen_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'first_touch_json' => 'array',
            'last_touch_json' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
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

    public function convertedLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'converted_lead_id');
    }
}
