<?php

namespace App\Models;

use App\Enums\AdmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admission extends Model
{
    protected $fillable = [
        'lead_id',
        'service_id',
        'pin_code_id',
        'service_location_page_id',
        'marketing_attribution_session_id',
        'status',
        'patient_name',
        'patient_phone',
        'notes',
        'admitted_at',
        'discharged_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AdmissionStatus::class,
            'admitted_at' => 'datetime',
            'discharged_at' => 'datetime',
        ];
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

    public function serviceLocationPage(): BelongsTo
    {
        return $this->belongsTo(ServiceLocationPage::class);
    }

    public function attributionSession(): BelongsTo
    {
        return $this->belongsTo(MarketingAttributionSession::class, 'marketing_attribution_session_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function revenueEvents(): HasMany
    {
        return $this->hasMany(RevenueEvent::class);
    }
}
