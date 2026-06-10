<?php

namespace App\Support;

/**
 * CMS pages that only render with runtime context ($vacancy, $service, etc.)
 * must not be browsed directly at /p/{slug}.
 */
final class InternalTemplatePageRedirects
{
    /**
     * @return array<string, string> slug => absolute redirect path
     */
    public static function map(): array
    {
        $map = [];

        $careersSlug = trim((string) config('careers.job_detail_page_slug', ''));
        if ($careersSlug !== '') {
            $map[$careersSlug] = '/careers';
        }

        $serviceSlug = trim((string) config('public_pages.service_detail_page_slug', ''));
        if ($serviceSlug !== '') {
            $map[$serviceSlug] = '/services-catalog';
        }

        return $map;
    }

    public static function redirectPathFor(string $slug): ?string
    {
        return self::map()[$slug] ?? null;
    }
}
