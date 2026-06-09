<?php

namespace App\Support;

use App\Models\Service;
use App\Models\ServiceCategory;

final class CoverageLinkContext
{
    /**
     * @return array<string, string>
     */
    public static function queryParams(?ServiceCategory $category = null, ?Service $service = null): array
    {
        $params = [];

        if ($category instanceof ServiceCategory && filled($category->code)) {
            $params['category'] = (string) $category->code;
        }

        if ($service instanceof Service && filled($service->service_code)) {
            $params['service'] = (string) $service->service_code;
        }

        return $params;
    }

    public static function append(string $url, ?ServiceCategory $category = null, ?Service $service = null): string
    {
        $params = self::queryParams($category, $service);

        if ($params === []) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query($params);
    }
}
