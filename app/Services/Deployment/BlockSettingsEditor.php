<?php

namespace App\Services\Deployment;

use App\Models\Block;
use App\Services\ContentParser;

class BlockSettingsEditor
{
    public function __construct(
        private readonly BlockSettingsResolver $resolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function settings(Block $block): array
    {
        return $this->resolver->resolve((string) $block->block_slug, $block);
    }

    /**
     * @param  array<string, mixed>  $partial  Keys: style_variant, media, section, content
     */
    public function save(Block $block, array $partial): Block
    {
        $current = is_array($block->settings_json) ? $block->settings_json : [];

        foreach (['style_variant', 'media', 'section', 'content'] as $key) {
            if (! array_key_exists($key, $partial)) {
                continue;
            }
            if ($key === 'content' && ! is_array($partial[$key])) {
                continue;
            }
            $current[$key] = $partial[$key];
        }

        $block->settings_json = $current;
        $block->save();

        return $block;
    }

    /**
     * @return array<string, array{label: string, type: string, default?: string}>
     */
    public function contentSchemaForBlock(Block $block): array
    {
        $slug = \App\Support\BlockContent::resolveSchemaSlug(
            (string) $block->block_slug,
            (string) $block->code
        );

        return \App\Support\BlockContent::schema($slug);
    }

    /**
     * @return list<string>
     */
    public function mediaSlotsForBlock(Block $block): array
    {
        $family = $this->resolver->familyForBlockType((string) $block->block_type);
        $slots = config('design_system.media_slots', []);

        if ($family !== null && isset($slots[$family])) {
            return $slots[$family];
        }

        return ['image'];
    }

    public function previewHtml(Block $block): string
    {
        $code = (string) $block->code;
        if (trim($code) === '') {
            return '';
        }

        $vars = $this->resolver->renderVariables((string) $block->block_slug, $block);

        return ContentParser::renderBlockCodeWithVariables(
            $code,
            0,
            null,
            (string) $block->block_slug,
            $vars,
        );
    }

    /**
     * @return list<string>
     */
    public function sectionControlKeys(): array
    {
        return config('design_system.section_controls', []);
    }

    /**
     * @return list<string>
     */
    public function styleVariants(): array
    {
        return array_keys(config('design_system.variant_classes', []));
    }
}
