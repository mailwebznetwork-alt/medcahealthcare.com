<?php

namespace App\Services\Operations;

use App\Models\Page;
use App\Models\Service;
use App\Services\SiteArchitect\ServiceInsertCatalog;

class ServiceRelatedPageTokens
{
    /**
     * @return list<string>
     */
    public function codesFromPageContent(?string $content, ?string $excludeCode = null): array
    {
        if (! is_string($content) || $content === '') {
            return [];
        }

        if (preg_match_all(ServiceInsertCatalog::SERVICE_TOKEN_PATTERN, $content, $matches) === false) {
            return [];
        }

        $codes = [];
        foreach ($matches[1] as $raw) {
            $code = strtolower(trim(str_replace([' ', '_'], '-', (string) $raw)));
            if ($code === '' || ($excludeCode !== null && $code === $excludeCode)) {
                continue;
            }
            $codes[$code] = $code;
        }

        return array_values($codes);
    }

    /**
     * @param  list<string>  $relatedCodes
     */
    public function applyToDetailPage(Service $service, array $relatedCodes): bool
    {
        $page = $service->detailPage;
        if ($page === null) {
            $page = app(ServiceDetailPageProvisioner::class)->findPageBySuggestedSlug($service);
        }

        if ($page === null) {
            return false;
        }

        $ownCode = strtolower(trim((string) $service->service_code));
        $relatedCodes = array_values(array_unique(array_filter(array_map(
            static fn (mixed $code): string => strtolower(trim(str_replace([' ', '_'], '-', (string) $code))),
            $relatedCodes
        ), static fn (string $code): bool => $code !== '' && $code !== $ownCode)));

        $content = (string) ($page->content ?? '');
        $marker = '{{block:service-detail-related}}';
        $tokenLines = implode("\n", array_map(
            static fn (string $code): string => '{{service:'.$code.'}}',
            $relatedCodes
        ));

        if (str_contains($content, $marker)) {
            $parts = explode($marker, $content, 2);
            $before = $parts[0];
            $after = $parts[1] ?? '';
            $after = preg_replace(
                ServiceInsertCatalog::SERVICE_TOKEN_PATTERN,
                '',
                $after
            ) ?? $after;
            $after = preg_replace("/^\s*\n+/m", "\n", $after) ?? $after;
            $middle = $tokenLines !== '' ? "\n".$tokenLines."\n" : "\n";
            $content = rtrim($before).$marker.$middle.ltrim($after);
        } elseif ($tokenLines !== '') {
            $content = rtrim($content)."\n\n".$tokenLines."\n";
        } else {
            $content = preg_replace(ServiceInsertCatalog::SERVICE_TOKEN_PATTERN, '', $content) ?? $content;
        }

        $page->forceFill(['content' => trim($content)])->save();

        return true;
    }
}
