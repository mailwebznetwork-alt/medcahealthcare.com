<?php

namespace App\Services\Deployment;

use App\Models\Block;
use App\Models\Page;
use App\Services\Content\ContentRenderContext;
use App\Services\Media\MediaReferenceResolver;

class BlockSettingsResolver
{
    public function __construct(
        private readonly StylePackRegistry $stylePacks,
        private readonly ContentRenderContext $renderContext,
    ) {}

    /**
     * Resolved settings for a block render (page overrides > block defaults > style pack).
     *
     * @return array<string, mixed>
     */
    public function resolve(string $blockSlug, ?Block $block = null, ?Page $page = null, ?string $stylePackSlug = null): array
    {
        $block ??= Block::query()->where('block_slug', $blockSlug)->first();
        $base = is_array($block?->settings_json) ? $block->settings_json : [];

        $overrides = [];
        $contextOverrides = $this->renderContext->all()['blockOverrides'] ?? [];
        if (is_array($contextOverrides[$blockSlug] ?? null)) {
            $overrides = $contextOverrides[$blockSlug];
        }
        if ($page !== null && is_array($page->block_overrides_json)) {
            $pageOverrides = is_array($page->block_overrides_json[$blockSlug] ?? null)
                ? $page->block_overrides_json[$blockSlug]
                : [];
            $overrides = array_replace_recursive($overrides, $pageOverrides);
        }

        $merged = array_replace_recursive($base, $overrides);

        if (! isset($merged['style_variant']) && $stylePackSlug !== null && $block !== null) {
            $family = $this->familyForBlockType((string) $block->block_type);
            $assignments = $this->stylePacks->assignments($stylePackSlug);
            if ($family !== null && isset($assignments[$family])) {
                $merged['style_variant'] = $assignments[$family];
            }
        }

        return $merged;
    }

    /**
     * Variables injected into ContentParser Blade render for this block.
     *
     * @return array<string, mixed>
     */
    public function renderVariables(string $blockSlug, ?Block $block = null, ?Page $page = null, ?string $stylePackSlug = null): array
    {
        $settings = $this->resolve($blockSlug, $block, $page, $stylePackSlug);
        $variant = (string) ($settings['style_variant'] ?? 'style_1');
        $variantClasses = config('design_system.variant_classes', []);
        $modifier = $variantClasses[$variant] ?? 'medca-block--style-1';
        $mediaRefs = is_array($settings['media_refs'] ?? null) ? $settings['media_refs'] : [];
        $mediaPaths = is_array($settings['media'] ?? null) ? $settings['media'] : [];
        $resolver = app(MediaReferenceResolver::class);
        $blockMedia = $resolver->mergeMediaPaths($mediaPaths, $mediaRefs);

        return [
            'blockSettings' => $settings,
            'blockStyleVariant' => $variant,
            'blockStyleClass' => $modifier,
            'blockMedia' => $blockMedia,
            'blockMediaRefs' => $mediaRefs,
            'blockSection' => is_array($settings['section'] ?? null) ? $settings['section'] : [],
            'blockSlug' => $blockSlug,
        ];
    }

    public function familyForBlockType(string $blockType): ?string
    {
        $map = config('design_system.block_type_families', []);

        return $map[$blockType] ?? null;
    }
}
