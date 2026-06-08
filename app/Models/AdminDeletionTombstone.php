<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminDeletionTombstone extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'entity_type',
        'natural_key',
        'deleted_at',
        'deleted_by_id',
        'source',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    public static function record(
        string $entityType,
        string $naturalKey,
        ?int $userId = null,
        ?string $source = null,
        ?string $reason = null,
    ): self {
        return self::query()->updateOrCreate(
            [
                'entity_type' => $entityType,
                'natural_key' => $naturalKey,
            ],
            [
                'deleted_at' => now(),
                'deleted_by_id' => $userId ?? auth()->id(),
                'source' => $source,
                'reason' => $reason,
            ]
        );
    }

    public static function exists(string $entityType, string $naturalKey): bool
    {
        return self::query()
            ->where('entity_type', $entityType)
            ->where('natural_key', $naturalKey)
            ->exists();
    }
}
