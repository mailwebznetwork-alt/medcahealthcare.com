<?php

namespace App\Support;

use App\Models\Integration;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves Google marketing credentials from the integrations registry.
 */
final class GoogleIntegrationCredentials
{
    /**
     * @return array<string, mixed>
     */
    public static function credentials(): array
    {
        if (! Schema::hasTable('integrations')) {
            return [];
        }

        foreach (['google_analytics', 'google_services', 'google_ads'] as $name) {
            $integration = Integration::query()->where('name', $name)->first();
            if ($integration !== null && is_array($integration->credentials) && $integration->credentials !== []) {
                return $integration->credentials;
            }
        }

        return [];
    }

    public static function measurementId(?string $fallback = null): ?string
    {
        $credentials = self::credentials();

        return $credentials['measurement_id']
            ?? $credentials['ga4_measurement_id']
            ?? $fallback;
    }

    public static function adsAwId(?string $fallback = null): ?string
    {
        $credentials = self::credentials();

        return $credentials['google_ads_aw_id']
            ?? $credentials['aw_id']
            ?? $fallback;
    }

    public static function propertyId(?string $fallback = null): ?string
    {
        $credentials = self::credentials();
        $propertyId = $credentials['property_id'] ?? $credentials['ga4_property_id'] ?? null;

        return is_string($propertyId) && $propertyId !== '' ? $propertyId : $fallback;
    }
}
