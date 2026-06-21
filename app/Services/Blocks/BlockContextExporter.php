<?php

namespace App\Services\Blocks;

use App\Models\Block;

class BlockContextExporter
{
    public function __construct(
        private readonly BlockContextResolver $resolver,
    ) {}

    /**
     * Combined export: Markdown body + JSON appendix for ChatGPT and Gemini.
     */
    public function export(Block $block): string
    {
        $payload = $this->resolver->resolve($block);

        return $this->toMarkdown($payload)."\n\n".$this->jsonAppendix($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Block $block): array
    {
        return $this->resolver->resolve($block);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function toMarkdown(array $payload): string
    {
        $lines = [
            '# MarkOnMinds Block Context Export',
            '',
            'Use this document to understand or edit a MarkOnMinds CMS block. Preserve the block slug on pages unless intentionally renaming.',
            '',
            '## Identity',
            '- **Name:** '.($payload['name'] ?? ''),
            '- **Slug:** `'.($payload['slug'] ?? '').'`',
            '- **Type:** '.($payload['block_type'] ?? '—'),
            '- **Managed (Git-synced):** '.(($payload['is_managed'] ?? false) ? 'yes' : 'no'),
            '- **Active:** '.(($payload['is_active'] ?? false) ? 'yes' : 'no'),
            '',
            '## Purpose',
            (string) ($payload['purpose'] ?? '_No description recorded._'),
            '',
        ];

        $lines[] = '## Content (resolved marketing copy)';
        $content = is_array($payload['content'] ?? null) ? $payload['content'] : [];
        if ($content === []) {
            $lines[] = '_No marketing copy fields resolved._';
        } else {
            foreach ($content as $key => $value) {
                $lines[] = '- **'.$key.':** '.$this->escapeInline((string) $value);
            }
        }
        $lines[] = '';

        $lines[] = '## HTML / Blade source';
        $blade = $payload['html_blade'] ?? null;
        if (is_string($blade) && $blade !== '') {
            $lines[] = '```blade';
            $lines[] = $blade;
            $lines[] = '```';
        } else {
            $lines[] = '_Blade source not resolved. Database code pointer:_';
            $lines[] = '```';
            $lines[] = (string) ($payload['code_pointer'] ?? '');
            $lines[] = '```';
        }
        $lines[] = '';

        $lines[] = '## CSS';
        $css = $payload['css'] ?? null;
        if (is_string($css) && $css !== '') {
            $lines[] = '```css';
            $lines[] = $css;
            $lines[] = '```';
        } else {
            $lines[] = '_No custom CSS._';
        }
        $lines[] = '';

        $lines[] = '## Schema';
        $schema = is_array($payload['schema'] ?? null) ? $payload['schema'] : [];
        $fieldDefs = is_array($schema['field_definitions'] ?? null) ? $schema['field_definitions'] : [];
        if ($fieldDefs === []) {
            $lines[] = '_No content schema defined._';
        } else {
            foreach ($fieldDefs as $key => $field) {
                if (! is_array($field)) {
                    continue;
                }
                $label = $field['label'] ?? $key;
                $type = $field['type'] ?? 'text';
                $default = isset($field['default']) ? ' — default: `'.$this->escapeInline((string) $field['default']).'`' : '';
                $lines[] = '- **'.$key.'** ('.$type.'): '.$label.$default;
            }
        }
        if (isset($schema['block_schema_json']) && is_array($schema['block_schema_json'])) {
            $lines[] = '';
            $lines[] = '**Block schema_json:**';
            $lines[] = '```json';
            $lines[] = json_encode($schema['block_schema_json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $lines[] = '```';
        }
        $lines[] = '';

        $lines[] = '## Settings (raw settings_json)';
        $settings = $payload['settings'] ?? null;
        if (is_array($settings) && $settings !== []) {
            $lines[] = '```json';
            $lines[] = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $lines[] = '```';
        } else {
            $lines[] = '_No settings_json stored._';
        }
        $lines[] = '';

        $lines[] = '## Usage locations';
        $usage = is_array($payload['usage'] ?? null) ? $payload['usage'] : [];
        if ($usage === []) {
            $lines[] = '_This block is not referenced on any page, blog, section, or nested block._';
        } else {
            foreach ($usage as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $type = $row['type'] ?? 'unknown';
                $title = $row['title'] ?? $row['slug'] ?? '';
                $slug = $row['slug'] ?? '';
                $url = $row['url'] ?? null;
                $active = ($row['is_active'] ?? false) ? 'active' : 'inactive';
                $urlPart = is_string($url) && $url !== '' ? ' — '.$url : '';
                $lines[] = '- **'.$type.'** `'.$slug.'` ('.$title.') — '.$active.$urlPart;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function jsonAppendix(array $payload): string
    {
        return "---\n\n## Structured JSON (Gemini / tooling)\n\n```json\n"
            .json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ."\n```";
    }

    private function escapeInline(string $value): string
    {
        return str_replace(['`', "\n"], ['\'', ' '], $value);
    }
}
