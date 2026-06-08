<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRemovedMapping extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'mapping_type',
        'natural_key',
        'service_code',
        'pincode',
        'removed_by_id',
        'source',
        'reason',
        'removed_at',
    ];

    protected function casts(): array
    {
        return [
            'removed_at' => 'datetime',
        ];
    }

    public static function servicePincodeKey(string $serviceCode, string $pincode): string
    {
        return strtolower(trim($serviceCode)).'@'.trim($pincode);
    }

    public static function recordServicePincodeRemoval(
        string $serviceCode,
        string $pincode,
        string $source = 'ui',
        ?string $reason = null,
        ?int $userId = null,
    ): self {
        $key = self::servicePincodeKey($serviceCode, $pincode);

        return self::query()->updateOrCreate(
            [
                'mapping_type' => 'service_pincode',
                'natural_key' => $key,
            ],
            [
                'service_code' => trim($serviceCode),
                'pincode' => trim($pincode),
                'removed_by_id' => $userId ?? auth()->id(),
                'source' => $source,
                'reason' => $reason,
                'removed_at' => now(),
            ]
        );
    }

    public static function isServicePincodeRemoved(string $serviceCode, string $pincode): bool
    {
        return self::query()
            ->where('mapping_type', 'service_pincode')
            ->where('natural_key', self::servicePincodeKey($serviceCode, $pincode))
            ->exists();
    }
}
