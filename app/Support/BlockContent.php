<?php

namespace App\Support;

use App\Services\Deployment\GlobalContentVariableRepository;

final class BlockContent
{
    /**
     * Marketing copy from block settings_json.content, then schema default.
     */
    public static function get(array $blockSettings, string $blockSlug, string $key, ?string $default = null): string
    {
        $content = is_array($blockSettings['content'] ?? null) ? $blockSettings['content'] : [];
        $value = $content[$key] ?? null;
        if (is_string($value) && trim($value) !== '') {
            $trimmed = trim($value);
            if (! self::isBladePlaceholder($trimmed)) {
                return $trimmed;
            }
        }

        $schemas = self::schema($blockSlug);
        if (isset($schemas[$key]['default'])) {
            $schemaDefault = trim((string) $schemas[$key]['default']);
            if ($schemaDefault !== '' && ! self::isBladePlaceholder($schemaDefault)) {
                return $schemaDefault;
            }
        }

        return $default ?? '';
    }

    /**
     * Schema defaults must not contain Blade — they are not compiled at render time.
     */
    public static function isBladePlaceholder(string $value): bool
    {
        return str_contains($value, '{{') || str_contains($value, '{!!');
    }

    /**
     * @return array<string, array{label: string, type: string, default?: string}>
     */
    public static function schema(string $blockSlug): array
    {
        $schema = config("block_content_schemas.blocks.{$blockSlug}", []);

        if (is_array($schema) && $schema !== []) {
            return $schema;
        }

        $defaults = config('block_content_schemas.default_fields', []);

        return is_array($defaults) ? $defaults : [];
    }

    public static function hasSchema(string $blockSlug): bool
    {
        if (array_key_exists($blockSlug, config('block_content_schemas.blocks', []))) {
            return true;
        }

        return array_key_exists($blockSlug, config('block_templates.templates', []));
    }

    /**
     * Schema slug for marketing copy: registry slug, else last segment of a blocks.* @include.
     */
    public static function resolveSchemaSlug(string $blockSlug, string $code = ''): string
    {
        if (self::hasSchema($blockSlug)) {
            return $blockSlug;
        }

        if (preg_match("/@include\s*\(\s*['\"]blocks\.([^'\"]+)['\"]/", $code, $matches) === 1) {
            $parts = explode('.', $matches[1]);
            $included = (string) end($parts);
            if ($included !== '' && self::hasSchema($included)) {
                return $included;
            }
        }

        return $blockSlug;
    }

    public static function marketingCopyVisible(string $blockSlug, string $code = ''): bool
    {
        $schemaSlug = self::resolveSchemaSlug($blockSlug, $code);

        return self::hasSchema($schemaSlug) && self::schema($schemaSlug) !== [];
    }

    /**
     * @return list<string>
     */
    public static function lines(array $blockSettings, string $blockSlug, string $key): array
    {
        $raw = self::get($blockSettings, $blockSlug, $key);

        return array_values(array_filter(array_map('trim', explode("\n", $raw))));
    }

    public static function global(string $key, ?string $fallback = null): string
    {
        $resolved = app(GlobalContentVariableRepository::class)->resolved();
        $value = $resolved[$key] ?? '';

        return $value !== '' ? $value : ($fallback ?? '');
    }

    public static function telHref(?string $fallback = 'tel:+918884999002'): string
    {
        $tel = self::global('phone_tel');

        return $tel !== '' ? $tel : ($fallback ?? '');
    }

    public static function phoneDisplay(?string $fallback = '+91 88849 99002'): string
    {
        $display = self::global('phone_number');

        return $display !== '' ? $display : ($fallback ?? '');
    }
}
