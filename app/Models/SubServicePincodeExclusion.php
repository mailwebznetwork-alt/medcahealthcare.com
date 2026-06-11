<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubServicePincodeExclusion extends Model
{
    protected $fillable = [
        'sub_service_id',
        'pincode_id',
    ];

    /**
     * @return BelongsTo<SubService, $this>
     */
    public function subService(): BelongsTo
    {
        return $this->belongsTo(SubService::class);
    }

    /**
     * @return BelongsTo<PinCode, $this>
     */
    public function pincode(): BelongsTo
    {
        return $this->belongsTo(PinCode::class, 'pincode_id');
    }
}
