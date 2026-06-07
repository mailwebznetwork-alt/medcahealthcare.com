<?php

namespace App\Services\Import;

/**
 * Shared parsing helpers for bulk imports.
 */
final class ImportSupport
{
    public static function parseBool(?string $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'y', 'on'], true);
    }

    /**
     * @return list<string>
     */
    public static function parseList(?string $value, string $separator = ','): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $v) => trim($v),
            preg_split('/['.preg_quote($separator, '/').'|]/', $value) ?: []
        )));
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public static function parseFaqPairs(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $pairs = [];
        foreach (explode(';;', $value) as $chunk) {
            $parts = explode('|', $chunk, 2);
            if (count($parts) === 2 && trim($parts[0]) !== '' && trim($parts[1]) !== '') {
                $pairs[] = ['question' => trim($parts[0]), 'answer' => trim($parts[1])];
            }
        }

        return $pairs;
    }

    public static function parseKeywords(?string $value): ?array
    {
        $list = self::parseList($value);

        return $list === [] ? null : $list;
    }

    /**
     * Normalize spreadsheet or legacy string values into a line list for array-cast columns.
     *
     * @return list<string>
     */
    public static function normalizeLineArray(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map(
                static fn (mixed $item): string => trim((string) $item),
                $value
            ), static fn (string $item): bool => $item !== ''));
        }

        if (! is_string($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return self::normalizeLineArray($decoded);
        }

        if (str_contains($value, '|')) {
            return self::parseList($value, '|');
        }

        return array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            preg_split('/\r\n|\r|\n/', $value) ?: []
        ), static fn (string $line): bool => $line !== ''));
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  array<string, string|null>  $defaults
     * @return array<string, string|null>
     */
    public static function mergeRowDefaults(array $row, array $defaults): array
    {
        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
                $row[$key] = $value;
            }
        }

        return $row;
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    public static function extractCustomFields(array $row, array $keys): array
    {
        $custom = [];
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $value = $row[$key];
            if ($value === null || $value === '') {
                continue;
            }
            $custom[$key] = $value;
        }

        return $custom;
    }

    public static function parseJson(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
