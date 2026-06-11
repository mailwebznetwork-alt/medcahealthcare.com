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

    public static function categoryPincodeKey(string $categoryCode, string $pincode): string
    {
        return ServiceCategory::normalizeCode($categoryCode).'@'.trim($pincode);
    }

    public static function recordCategoryPincodeRemoval(
        string $categoryCode,
        string $pincode,
        string $source = 'ui',
        ?string $reason = null,
        ?int $userId = null,
    ): self {
        $key = self::categoryPincodeKey($categoryCode, $pincode);

        return self::query()->updateOrCreate(
            [
                'mapping_type' => 'category_pincode',
                'natural_key' => $key,
            ],
            [
                'service_code' => ServiceCategory::normalizeCode($categoryCode),
                'pincode' => trim($pincode),
                'removed_by_id' => $userId ?? auth()->id(),
                'source' => $source,
                'reason' => $reason,
                'removed_at' => now(),
            ]
        );
    }

    public static function clearCategoryPincodeRemoval(string $categoryCode, string $pincode): void
    {
        self::query()
            ->where('mapping_type', 'category_pincode')
            ->where('natural_key', self::categoryPincodeKey($categoryCode, $pincode))
            ->delete();
    }

    public static function isCategoryPincodeRemoved(string $categoryCode, string $pincode): bool
    {
        return self::query()
            ->where('mapping_type', 'category_pincode')
            ->where('natural_key', self::categoryPincodeKey($categoryCode, $pincode))
            ->exists();
    }

    public static function clearServicePincodeRemoval(string $serviceCode, string $pincode): void
    {
        self::query()
            ->where('mapping_type', 'service_pincode')
            ->where('natural_key', self::servicePincodeKey($serviceCode, $pincode))
            ->delete();
    }
}
