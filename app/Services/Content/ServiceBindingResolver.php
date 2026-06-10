<?php

namespace App\Services\Content;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Service;
use App\Services\ServiceContextCollector;

/**
 * Resolves {{service:TOKEN}} references against the live services catalog.
 * Tokens may be service_code, numeric id, or a configured legacy alias.
 */
class ServiceBindingResolver
{
    public function __construct(
        private readonly ServiceBindingRegistry $registry,
        private readonly ServiceContextCollector $collector,
    ) {}

    /**
     * @return list<string>
     */
    public function extractTokens(string $code): array
    {
        if ($code === '' || preg_match_all(ServiceTokenPattern::PATTERN, $code, $matches) === false) {
            return [];
        }

        $tokens = [];
        foreach ($matches[1] ?? [] as $raw) {
            $token = trim((string) $raw);
            if ($token !== '') {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    public function resolveForBlock(string $token): ?Service
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $cacheKey = $this->cacheKey($token);
        $cached = $this->registry->get($cacheKey);
        if ($cached instanceof Service) {
            $service = $this->reloadService($cached);
            if ($service === null) {
                return null;
            }

            $this->registerPublishedSchema($service);

            return $service;
        }

        $service = $this->lookup($token);
        if ($service === null) {
            return null;
        }

        $service->loadMissing(['seo', 'faqs', 'pincodes']);
        $this->registry->remember($cacheKey, $service);
        $this->registry->remember($this->cacheKey((string) $service->service_code), $service);

        $this->registerPublishedSchema($service);

        return $service;
    }

    private function reloadService(Service $cached): ?Service
    {
        $service = Service::query()
            ->where('is_active', true)
            ->find($cached->id);

        if ($service === null) {
            return null;
        }

        $service->loadMissing(['seo', 'faqs', 'pincodes']);

        return $service;
    }

    private function registerPublishedSchema(Service $service): void
    {
        if (
            $service->publish_status !== PublishStatus::Published
            || $service->visibility !== ServiceVisibility::Public
        ) {
            return;
        }

        try {
            $this->collector->register($service);
        } catch (\Throwable) {
            // Best-effort schema registration.
        }
    }

    public function resolvePublishedForHead(string $token): ?Service
    {
        $service = $this->resolveForBlock($token);
        if ($service === null || ! $service->isListedPublicly()) {
            return null;
        }

        return $service;
    }

    private function lookup(string $token): ?Service
    {
        $service = Service::findForBlockBinding($token);
        if ($service !== null) {
            return $service;
        }

        $service = Service::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(service_code) = ?', [strtolower($token)])
            ->first();
        if ($service !== null) {
            return $service;
        }

        if (ctype_digit($token)) {
            return Service::query()
                ->where('is_active', true)
                ->find((int) $token);
        }

        $alias = $this->configuredAlias($token);
        if ($alias !== null && $alias !== $token) {
            return $this->lookup($alias);
        }

        return null;
    }

    private function configuredAlias(string $token): ?string
    {
        $aliases = config('service_bindings.aliases', []);
        if (! is_array($aliases)) {
            return null;
        }

        $resolved = $aliases[$token] ?? $aliases[strtolower($token)] ?? null;

        return is_string($resolved) && trim($resolved) !== '' ? trim($resolved) : null;
    }

    private function cacheKey(string $token): string
    {
        return strtolower(trim($token));
    }
}
