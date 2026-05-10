<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Support\Collection;

/**
 * Request-scoped accumulator for {{service:CODE}} tokens encountered during
 * ContentParser execution. The page layout reads from this collector at
 * render time to emit Schema.org JSON-LD into <head> regardless of how the
 * admin chose to display the service in their block markup.
 *
 * Bound as a singleton in AppServiceProvider, so the lifetime is per HTTP
 * request when used through the IoC container.
 */
class ServiceContextCollector
{
    /**
     * Keyed by service_code so the same token used in multiple blocks emits
     * structured data only once.
     *
     * @var array<string, Service>
     */
    private array $services = [];

    public function register(Service $service): void
    {
        if (! is_string($service->service_code) || $service->service_code === '') {
            return;
        }

        $this->services[$service->service_code] = $service;
    }

    public function has(string $code): bool
    {
        return isset($this->services[$code]);
    }

    public function get(string $code): ?Service
    {
        return $this->services[$code] ?? null;
    }

    /**
     * @return Collection<string, Service>
     */
    public function collected(): Collection
    {
        return collect($this->services);
    }

    public function isEmpty(): bool
    {
        return $this->services === [];
    }

    public function count(): int
    {
        return count($this->services);
    }

    public function reset(): void
    {
        $this->services = [];
    }
}
