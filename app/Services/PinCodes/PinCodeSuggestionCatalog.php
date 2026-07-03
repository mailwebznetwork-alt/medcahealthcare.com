<?php

namespace App\Services\PinCodes;

use App\Models\PinCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Cached in-memory pincode catalog for fast modal autocomplete.
 */
final class PinCodeSuggestionCatalog
{
    private const string CACHE_KEY = 'pin_codes.suggestion_catalog.v1';

    private const int CACHE_TTL_SECONDS = 3600;

    /**
     * @return list<array{pincode: string, area_name: string, city: string}>
     */
    public function rows(): array
    {
        if (! Schema::hasTable('pin_codes')) {
            return [];
        }

        if (app()->environment('testing')) {
            return $this->loadRows();
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn (): array => $this->loadRows());
    }

    /**
     * @return array{pincode: string, area_name: string, city: string}|null
     */
    public function findExact(string $pincode): ?array
    {
        foreach ($this->rows() as $row) {
            if ($row['pincode'] === $pincode) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return list<array{pincode: string, area_name: string, city: string}>
     */
    public function matchPrefix(string $prefix, int $limit = 20): array
    {
        if ($prefix === '') {
            return [];
        }

        $matches = [];

        foreach ($this->rows() as $row) {
            if (! str_starts_with($row['pincode'], $prefix)) {
                continue;
            }

            $matches[] = $row;

            if (count($matches) >= $limit) {
                break;
            }
        }

        return $matches;
    }

    /**
     * @return list<array{pincode: string, area_name: string, city: string}>
     */
    private function loadRows(): array
    {
        return PinCode::query()
            ->where('is_active', true)
            ->orderBy('pincode')
            ->get(['pincode', 'area_name', 'city'])
            ->map(static fn (PinCode $record): array => [
                'pincode' => (string) $record->pincode,
                'area_name' => (string) $record->area_name,
                'city' => (string) $record->city,
            ])
            ->all();
    }
}
