<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueEvent extends Model
{
    protected $fillable = [
        'admission_id',
        'lead_id',
        'service_id',
        'pin_code_id',
        'service_category_id',
        'marketing_attribution_session_id',
        'amount',
        'currency',
        'label',
        'notes',
        'recorded_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
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

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function attributionSession(): BelongsTo
    {
        return $this->belongsTo(MarketingAttributionSession::class, 'marketing_attribution_session_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
