<?php

namespace App\Services\Discovery;

use App\Models\Service;
use App\Services\Import\ImportSupport;

/**
 * Workbook-driven discovery flags stored on services.custom_fields.
 */
final class ServiceDiscoveryVisibility
{
    public function allowsOnSurface(Service $service, string $surface): bool
    {
        $custom = is_array($service->custom_fields) ? $service->custom_fields : [];

        $key = match ($surface) {
            'category' => 'show_on_category_pages',
            'location' => 'show_on_location_pages',
            default => null,
        };

        if ($key === null || ! array_key_exists($key, $custom)) {
            return true;
        }

        return ImportSupport::parseBool(is_string($custom[$key]) ? $custom[$key] : null, true);
    }

    public function displayPriority(Service $service): int
    {
        $custom = is_array($service->custom_fields) ? $service->custom_fields : [];
        if (isset($custom['display_priority']) && is_numeric($custom['display_priority'])) {
            return (int) $custom['display_priority'];
        }

        return (int) ($service->sort_order ?? 0);
    }
}
