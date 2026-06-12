<?php

namespace App\Services\Import;

/**
 * Shared catalog content columns for categories, services, and sub-services.
 */
final class ImportCatalogContentMapper
{
    /**
     * @param  array<string, string|null>  $row
     * @return array<string, mixed>
     */
    public function contentAttributes(array $row): array
    {
        $attrs = array_filter([
            'short_summary' => $row['short_summary'] ?? null,
            'description' => $row['description'] ?? null,
            'key_benefits' => self::lineArrayOrNull($row['key_benefits'] ?? null),
            'eligibility' => self::lineArrayOrNull($row['eligibility'] ?? null),
            'process_steps' => self::lineArrayOrNull($row['process_steps'] ?? null),
            'ai_summary' => $row['ai_summary'] ?? null,
            'procedures' => self::lineArrayOrNull($row['procedures'] ?? null),
            'specialized_care' => self::lineArrayOrNull($row['specialized_care'] ?? null),
            'shifts' => self::lineArrayOrNull($row['shifts'] ?? null),
            'price_range' => $row['price_range'] ?? null,
            'featured_image' => $row['featured_image_url'] ?? null,
            'icon' => $row['icon_url'] ?? null,
            'line_icon' => $row['line_icon'] ?? null,
            'image_alt' => $row['image_alt'] ?? null,
            'target_keywords' => ImportSupport::parseKeywords($row['target_keywords'] ?? null),
            'ai_keywords' => ImportSupport::parseKeywords($row['ai_keywords'] ?? null),
        ], static fn ($v) => $v !== null && $v !== '');

        $gallery = ImportSupport::parseList($row['gallery_image_urls'] ?? null, '|');
        if ($gallery !== []) {
            $attrs['gallery'] = $gallery;
        }

        $trustSignals = ImportSupport::parseJson($row['trust_signals'] ?? null);
        if ($trustSignals !== null) {
            $attrs['trust_signals'] = $trustSignals;
        }

        return $attrs;
    }

    /**
     * @return list<string>|null
     */
    private static function lineArrayOrNull(mixed $value): ?array
    {
        $lines = ImportSupport::normalizeLineArray($value);

        return $lines === [] ? null : $lines;
    }
}
