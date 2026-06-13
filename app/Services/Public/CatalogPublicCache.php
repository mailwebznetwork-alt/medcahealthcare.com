<?php

namespace App\Services\Public;

use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use Illuminate\Support\Facades\Cache;

class CatalogPublicCache
{
    public function __construct(
        private readonly PublicDisplayNameResolver $resolver,
    ) {}

    /**
     * @return array{title: string, meta_title: string, meta_description: string|null, prefer_live_schema: bool, hreflang?: array<string, string>}
     */
    public function documentMeta(
        ?Page $page = null,
        ?Service $service = null,
        ?ServiceCategory $category = null,
        ?ServiceLocationPage $mapping = null,
        ?SubService $subService = null,
    ): array {
        if (! config('public_cache.enabled', true)) {
            return $this->resolver->documentMeta($page, $service, $category, $mapping, $subService);
        }

        $key = $this->metaKey($page, $service, $category, $mapping, $subService);
        $ttl = (int) config('public_cache.ttl', 3600);
        $store = config('public_cache.store');

        return Cache::store($store)->remember(
            $key,
            $ttl,
            fn (): array => $this->resolver->documentMeta($page, $service, $category, $mapping, $subService),
        );
    }

    public function forgetForService(Service $service): void
    {
        Cache::store(config('public_cache.store'))->forget(
            config('public_cache.prefix').':meta:service:'.$service->service_code
        );
    }

    private function metaKey(
        ?Page $page,
        ?Service $service,
        ?ServiceCategory $category,
        ?ServiceLocationPage $mapping,
        ?SubService $subService,
    ): string {
        $prefix = config('public_cache.prefix', 'medca_public');

        if ($mapping !== null) {
            return "{$prefix}:meta:location:{$mapping->id}";
        }
        if ($subService !== null) {
            return "{$prefix}:meta:sub:{$subService->sub_service_code}";
        }
        if ($service !== null) {
            return "{$prefix}:meta:service:{$service->service_code}";
        }
        if ($category !== null) {
            return "{$prefix}:meta:category:{$category->code}";
        }
        if ($page !== null) {
            return "{$prefix}:meta:page:{$page->slug}";
        }

        return "{$prefix}:meta:default";
    }
}
