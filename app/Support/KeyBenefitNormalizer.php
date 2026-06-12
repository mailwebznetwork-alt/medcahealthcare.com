<?php

namespace App\Support;

use App\Services\Public\CatalogLineIconMapper;

/**
 * Normalizes key_benefits JSON (legacy strings or {label, icon} objects).
 */
final class KeyBenefitNormalizer
{
    /**
     * @return list<array{label: string, icon: string}>
     */
    public static function expand(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $mapper = app(CatalogLineIconMapper::class);
        $items = [];

        foreach ($value as $entry) {
            if (is_string($entry)) {
                $label = trim($entry);
                if ($label === '') {
                    continue;
                }

                $items[] = [
                    'label' => $label,
                    'icon' => $mapper->benefitIcon($label),
                ];

                continue;
            }

            if (! is_array($entry)) {
                continue;
            }

            $label = trim((string) ($entry['label'] ?? $entry['text'] ?? ''));
            if ($label === '') {
                continue;
            }

            $icon = trim((string) ($entry['icon'] ?? ''));
            if ($icon === '') {
                $icon = $mapper->benefitIcon($label);
            }

            $items[] = [
                'label' => $label,
                'icon' => $icon,
            ];
        }

        return $items;
    }

    /**
     * @param  list<array{label: string, icon: string}>  $items
     * @return list<array{label: string, icon: string}>
     */
    public static function serialize(array $items): array
    {
        return array_values(array_map(static fn (array $item): array => [
            'label' => trim($item['label']),
            'icon' => trim($item['icon']),
        ], array_filter($items, static fn (array $item): bool => trim($item['label']) !== '')));
    }

    /**
     * @param  list<string>  $labels
     * @return list<array{label: string, icon: string}>
     */
    public static function fromLabels(array $labels): array
    {
        $mapper = app(CatalogLineIconMapper::class);

        return self::serialize(array_map(
            static fn (string $label): array => [
                'label' => $label,
                'icon' => $mapper->benefitIcon($label),
            ],
            array_values(array_filter(array_map(
                static fn (string $label): string => trim($label),
                $labels
            ), static fn (string $label): bool => $label !== ''))
        ));
    }

    /**
     * @return list<string>
     */
    public static function labelLines(mixed $value): array
    {
        return \App\Services\Import\ImportSupport::normalizeLineArray($value);
    }
}
