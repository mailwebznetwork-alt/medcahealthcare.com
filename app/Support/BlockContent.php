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

    /**
     * Prefer explicit block copy, then global content, then schema default.
     */
    public static function globalOrBlock(array $blockSettings, string $blockSlug, string $blockKey, string $globalKey, ?string $default = null): string
    {
        $explicit = self::explicitBlockContent($blockSettings, $blockKey);
        if ($explicit !== null) {
            return $explicit;
        }

        $global = self::global($globalKey, '');
        if ($global !== '') {
            return $global;
        }

        return self::get($blockSettings, $blockSlug, $blockKey, $default);
    }

    /**
     * @return list<string>
     */
    public static function globalLinesOrBlock(array $blockSettings, string $blockSlug, string $blockKey, string $globalKey): array
    {
        $explicit = self::explicitBlockContent($blockSettings, $blockKey);
        if ($explicit !== null) {
            return array_values(array_filter(array_map('trim', explode("\n", $explicit))));
        }

        $globalLines = self::linesFromGlobal($globalKey);
        if ($globalLines !== []) {
            return $globalLines;
        }

        return self::lines($blockSettings, $blockSlug, $blockKey);
    }

    private static function explicitBlockContent(array $blockSettings, string $blockKey): ?string
    {
        $content = is_array($blockSettings['content'] ?? null) ? $blockSettings['content'] : [];
        $value = $content[$blockKey] ?? null;
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || self::isBladePlaceholder($trimmed)) {
            return null;
        }

        return $trimmed;
    }

    /**
     * @return list<string>
     */
    public static function linesFromGlobal(string $globalKey): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", self::global($globalKey)))));
    }

    public static function telHref(?string $fallback = null): string
    {
        $tel = trim(self::global('phone_tel'));

        if ($tel === '') {
            $tel = trim((string) ($fallback ?? config('medca.phone_tel', '')));
        }

        if ($tel === '') {
            return '';
        }

        if (str_starts_with(strtolower($tel), 'tel:')) {
            return $tel;
        }

        return 'tel:'.preg_replace('/\s+/', '', $tel);
    }

    public static function callUsLabel(): string
    {
        return (string) __('Call Us');
    }

    public static function whatsAppUrl(?string $fallback = null): string
    {
        $url = trim(self::global('whatsapp'));

        if ($url !== '') {
            return $url;
        }

        $fallback = trim((string) ($fallback ?? config('medca.whatsapp_url', '')));

        return $fallback !== '' ? $fallback : app(\App\Services\Integrations\WhatsAppClickToChatService::class)->primaryUrl();
    }

    public static function phoneDisplay(?string $fallback = '+91 8593 000 360'): string
    {
        $display = self::global('phone_number');

        return $display !== '' ? $display : ($fallback ?? '');
    }
}
