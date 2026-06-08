<?php

namespace App\Services\Blocks;

use App\Models\Block;
use App\Support\BlockContent;
use Illuminate\Support\Facades\File;

class BlockContextResolver
{
    public function __construct(
        private readonly BlockUsageFinder $usageFinder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resolve(Block $block): array
    {
        $slug = (string) $block->block_slug;
        $code = (string) $block->code;
        $settings = is_array($block->settings_json) ? $block->settings_json : [];
        $schemaSlug = BlockContent::resolveSchemaSlug($slug, $code);
        $schemaDefinition = BlockContent::schema($schemaSlug);
        $template = config("block_templates.templates.{$slug}", []);

        return [
            'export_version' => '1.0',
            'export_type' => 'medca_block_context',
            'generated_at' => now()->toIso8601String(),
            'name' => (string) $block->block_name,
            'slug' => $slug,
            'block_type' => $block->block_type,
            'is_managed' => (bool) $block->is_managed,
            'is_active' => (bool) $block->is_active,
            'lifecycle_state' => (string) ($block->lifecycle_state ?? 'active'),
            'purpose' => $this->purpose($block, $template),
            'content' => $this->resolvedContent($settings, $schemaSlug, $schemaDefinition),
            'code_pointer' => $code,
            'html_blade' => $this->resolveBladeSource($code),
            'css' => filled($block->custom_css) ? (string) $block->custom_css : null,
            'schema' => [
                'schema_slug' => $schemaSlug,
                'field_definitions' => $schemaDefinition,
                'block_schema_json' => is_array($block->schema_json) ? $block->schema_json : null,
            ],
            'settings' => $settings !== [] ? $settings : null,
            'template' => is_array($template) && $template !== [] ? [
                'category' => $template['category'] ?? null,
                'view' => $template['view'] ?? null,
                'description' => $template['description'] ?? null,
            ] : null,
            'usage' => $this->usageFinder->findForSlug($slug),
        ];
    }

    /**
     * @param  array<string, mixed>  $template
     */
    private function purpose(Block $block, array $template): ?string
    {
        if (filled($block->description)) {
            return (string) $block->description;
        }

        $fromTemplate = $template['description'] ?? null;

        return is_string($fromTemplate) && $fromTemplate !== '' ? $fromTemplate : null;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, array<string, mixed>>  $schemaDefinition
     * @return array<string, string>
     */
    private function resolvedContent(array $settings, string $schemaSlug, array $schemaDefinition): array
    {
        $content = [];

        foreach ($schemaDefinition as $key => $field) {
            if (! is_string($key)) {
                continue;
            }
            $value = BlockContent::get($settings, $schemaSlug, $key);
            if ($value !== '') {
                $content[$key] = $value;
            }
        }

        $stored = is_array($settings['content'] ?? null) ? $settings['content'] : [];
        foreach ($stored as $key => $value) {
            if (is_string($key) && is_string($value) && trim($value) !== '' && ! isset($content[$key])) {
                $content[$key] = trim($value);
            }
        }

        return $content;
    }

    private function resolveBladeSource(string $code): ?string
    {
        if (preg_match("/@include\s*\(\s*['\"]([^'\"]+)['\"]/", $code, $matches) === 1) {
            $view = $matches[1];
            $path = resource_path('views/'.str_replace('.', '/', $view).'.blade.php');
            if (File::isFile($path)) {
                return File::get($path);
            }

            return null;
        }

        $trimmed = trim($code);

        return $trimmed !== '' ? $trimmed : null;
    }
}
