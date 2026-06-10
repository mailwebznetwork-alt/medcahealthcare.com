<?php

namespace App\Services\Content;

use App\Models\Service;
use Illuminate\Support\Collection;

/**
 * Builds the ordered $services collection for block templates from tokens,
 * with a live-catalog fallback when every token is stale after import.
 */
class BlockBoundServicesResolver
{
    public function __construct(
        private readonly ServiceBindingResolver $binding,
    ) {}

    /**
     * @return Collection<int, Service>
     */
    public function orderedForBlockCode(string $code, ?string $blockSlug = null): Collection
    {
        $ordered = $this->orderedFromTokens($code);
        if ($ordered->isNotEmpty()) {
            return $ordered;
        }

        return $this->catalogFallback($blockSlug);
    }

    /**
     * @return Collection<int, Service>
     */
    public function orderedFromTokens(string $code): Collection
    {
        $services = collect();
        $seen = [];

        foreach ($this->binding->extractTokens($code) as $token) {
            $service = $this->binding->resolveForBlock($token);
            if ($service === null || isset($seen[$service->id])) {
                continue;
            }

            $seen[$service->id] = true;
            $services->push($service);
        }

        return $services;
    }

    /**
     * @return Collection<int, Service>
     */
    private function catalogFallback(?string $blockSlug): Collection
    {
        if ($blockSlug === null || $blockSlug === '') {
            return collect();
        }

        $rules = config('service_bindings.catalog_fallback.'.$blockSlug);
        if (! is_array($rules)) {
            return collect();
        }

        $limit = max(1, (int) ($rules['limit'] ?? 6));
        $preferFeatured = (bool) ($rules['prefer_featured'] ?? false);

        $query = Service::query()->publicListing();

        if ($preferFeatured) {
            $featured = (clone $query)->where('is_featured', true)->limit($limit)->get();
            if ($featured->isNotEmpty()) {
                return $featured;
            }
        }

        return $query->limit($limit)->get();
    }
}
