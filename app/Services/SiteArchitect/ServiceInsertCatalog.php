<?php

namespace App\Services\SiteArchitect;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

/**
 * Site Architect block editors: every service row for the insert dropdown,
 * regardless of publish/visibility/active — public rendering still uses
 * {@see Service::findPublishedByCode()}.
 */
class ServiceInsertCatalog
{
    public const string SERVICE_TOKEN_PATTERN = \App\Services\Content\ServiceTokenPattern::PATTERN;

    /**
     * @return Collection<int, Service>
     */
    public function forDropdown(): Collection
    {
        return Service::query()
            ->orderBy('title')
            ->get([
                'id',
                'title',
                'service_code',
                'publish_status',
                'visibility',
                'is_active',
            ]);
    }

    public function existsForToken(string $serviceCode): bool
    {
        $code = trim($serviceCode);

        if ($code === '') {
            return false;
        }

        return Service::query()
            ->where('service_code', $code)
            ->exists();
    }
}
