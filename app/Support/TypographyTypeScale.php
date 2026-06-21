<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * Medca Consultancy public type scale — stored in theme typography JSON (type_scale key).
 */
final class TypographyTypeScale
{
    public const ELEMENT_KEYS = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'body_large', 'body_regular', 'small', 'button',
    ];

    public const BREAKPOINTS = ['desktop', 'tablet', 'mobile'];

    /**
     * @return array<string, array{family: string, desktop: array{size: float, weight: int, line_height: float}, tablet: array{size: float, weight: int, line_height: float}, mobile: array{size: float, weight: int, line_height: float}}>
     */
    public static function defaults(): array
    {
        $elements = config('typography.elements', []);

        if (! is_array($elements) || $elements === []) {
            return [];
        }

        return json_decode(json_encode($elements), true) ?: [];
    }

    /**
     * Deep-merge stored scale onto config defaults.
     *
     * @param  array<string, mixed>|null  $stored
     * @return array<string, array{family: string, desktop: array{size: float, weight: int, line_height: float}, tablet: array{size: float, weight: int, line_height: float}, mobile: array{size: float, weight: int, line_height: float}}>
     */
    public static function normalize(?array $stored): array
    {
        $base = self::defaults();
        if ($stored === null || $stored === []) {
            return $base;
        }

        foreach (self::ELEMENT_KEYS as $key) {
            if (! isset($base[$key])) {
                continue;
            }

            $incoming = is_array($stored[$key] ?? null) ? $stored[$key] : [];
            if (isset($incoming['family']) && is_string($incoming['family'])) {
                $base[$key]['family'] = in_array($incoming['family'], ['heading', 'body'], true)
                    ? $incoming['family']
                    : $base[$key]['family'];
            }

            foreach (self::BREAKPOINTS as $bp) {
                $bpIn = is_array($incoming[$bp] ?? null) ? $incoming[$bp] : [];
                foreach (['size', 'weight', 'line_height'] as $field) {
                    if (array_key_exists($field, $bpIn)) {
                        $base[$key][$bp][$field] = self::castField($field, $bpIn[$field], $base[$key][$bp][$field]);
                    }
                }
            }
        }

        return $base;
    }

    /**
     * @param  array<string, mixed>  $typeScale
     */
    public static function assertValid(array $typeScale): void
    {
        $normalized = self::normalize($typeScale);

        foreach (self::ELEMENT_KEYS as $key) {
            foreach (self::BREAKPOINTS as $bp) {
                $row = $normalized[$key][$bp];
                $size = (float) $row['size'];
                $weight = (int) $row['weight'];
                $lh = (float) $row['line_height'];

                if ($size < 0.5 || $size > 6) {
                    throw ValidationException::withMessages([
                        "type_scale.{$key}.{$bp}.size" => __('Size must be between 0.5 and 6 rem.'),
                    ]);
                }

                if ($weight < 100 || $weight > 900) {
                    throw ValidationException::withMessages([
                        "type_scale.{$key}.{$bp}.weight" => __('Weight must be between 100 and 900.'),
                    ]);
                }

                if ($lh < 1 || $lh > 2.5) {
                    throw ValidationException::withMessages([
                        "type_scale.{$key}.{$bp}.line_height" => __('Line height must be between 1 and 2.5.'),
                    ]);
                }
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public static function elementLabels(): array
    {
        return [
            'h1' => 'H1',
            'h2' => 'H2',
            'h3' => 'H3',
            'h4' => 'H4',
            'h5' => 'H5',
            'h6' => 'H6',
            'body_large' => 'Body Large',
            'body_regular' => 'Body Regular',
            'small' => 'Small Text',
            'button' => 'Button Text',
        ];
    }

    /**
     * @param  array<string, mixed>  $typography
     * @return array<string, mixed>
     */
    public static function mergeIntoTypography(array $typography): array
    {
        $typography['type_scale'] = self::normalize(
            is_array($typography['type_scale'] ?? null) ? $typography['type_scale'] : null
        );

        return $typography;
    }

    private static function castField(string $field, mixed $value, mixed $fallback): mixed
    {
        return match ($field) {
            'size' => round(max(0.5, min(6, (float) $value)), 4),
            'weight' => (int) max(100, min(900, (int) $value)),
            'line_height' => round(max(1, min(2.5, (float) $value)), 2),
            default => $fallback,
        };
    }
}
