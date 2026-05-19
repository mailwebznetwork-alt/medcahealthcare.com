<?php

namespace App\Services\Content;

use App\Models\Service;

/**
 * Request-scoped cache for {{service:CODE}} loads — avoids duplicate queries
 * when the same service appears in multiple blocks on one page.
 */
class ServiceBindingRegistry
{
    /** @var array<string, Service> */
    private array $byCode = [];

    public function remember(string $serviceCode, Service $service): Service
    {
        $this->byCode[$serviceCode] = $service;

        return $service;
    }

    public function get(string $serviceCode): ?Service
    {
        return $this->byCode[$serviceCode] ?? null;
    }

    public function flush(): void
    {
        $this->byCode = [];
    }
}
