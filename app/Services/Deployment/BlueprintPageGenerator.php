<?php

namespace App\Services\Deployment;

use App\Enums\PageLayoutMode;
use App\Models\DeploymentGeneration;
use App\Models\Page;
use App\Models\ThemeConfiguration;
use App\Models\User;
use App\Services\Theme\ThemeConfigRepository;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BlueprintPageGenerator
{
    public function __construct(
        private readonly BlueprintRegistry $blueprints,
        private readonly StylePackRegistry $stylePacks,
        private readonly ThemeConfigRepository $themeRepository,
    ) {}

    /**
     * Generate pages from blueprint into standard Site Architect Page records.
     *
     * @return array{generation: DeploymentGeneration, pages: list<Page>}
     */
    public function generate(
        string $blueprintSlug,
        string $stylePackSlug,
        ?string $themePresetSlug,
        string $layoutPreset,
        User $user,
        bool $applyThemeToDraft = true,
        bool $activatePages = false,
    ): array {
        $blueprint = $this->blueprints->find($blueprintSlug);
        if ($blueprint === null) {
            throw ValidationException::withMessages(['blueprint' => __('Unknown blueprint.')]);
        }

        if ($this->stylePacks->find($stylePackSlug) === null) {
            throw ValidationException::withMessages(['style_pack' => __('Unknown style pack.')]);
        }

        $themePresetSlug ??= (string) ($this->stylePacks->find($stylePackSlug)['theme_preset_slug'] ?? 'clinical_blue');

        $pages = [];
        $slugs = [];

        foreach ($this->pageDefinitions($blueprint) as $definition) {
            $page = $this->upsertPage($definition, $blueprintSlug, $stylePackSlug, $themePresetSlug, $layoutPreset, $activatePages);
            $pages[] = $page;
            $slugs[] = $page->slug;
        }

        if ($applyThemeToDraft) {
            $this->themeRepository->applyPresetToDraft($themePresetSlug, $user);
            $pack = $this->stylePacks->find($stylePackSlug);
            if (is_array($pack)) {
                $header = (string) ($pack['header_preset'] ?? 'classic_healthcare career consultancy');
                $layout = $layoutPreset !== '' ? $layoutPreset : (string) ($pack['layout_preset'] ?? 'contained');
                $this->themeRepository->saveDraftMeta($header, $layout, [], $user);
            }
            $config = ThemeConfiguration::current();
            $config->update(['draft_style_pack' => $stylePackSlug]);
        }

        $generation = DeploymentGeneration::query()->create([
            'blueprint_slug' => $blueprintSlug,
            'style_pack_slug' => $stylePackSlug,
            'theme_preset_slug' => $themePresetSlug,
            'layout_preset' => $layoutPreset,
            'generated_page_slugs' => $slugs,
            'status' => 'draft',
            'generated_by_id' => $user->id,
        ]);

        return ['generation' => $generation, 'pages' => $pages];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function upsertPage(
        array $definition,
        string $blueprintSlug,
        string $stylePackSlug,
        string $themePresetSlug,
        string $layoutPreset,
        bool $activatePages = false,
    ): Page {
        $slug = (string) ($definition['slug'] ?? '');
        $blocks = is_array($definition['blocks'] ?? null) ? $definition['blocks'] : [];
        $overrides = [];
        $parts = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }
            $blockSlug = (string) ($block['slug'] ?? '');
            if ($blockSlug === '') {
                continue;
            }
            $parts[] = ['type' => 'block', 'slug' => $blockSlug];
            $overrides[$blockSlug] = array_filter([
                'style_variant' => $block['style_variant'] ?? null,
                'media' => $block['media'] ?? null,
                'section' => $block['section'] ?? null,
            ], fn ($v) => $v !== null);
        }

        $layoutMode = match ((string) ($definition['layout_mode'] ?? 'contained')) {
            'canvas' => PageLayoutMode::Canvas,
            default => PageLayoutMode::Contained,
        };

        $page = Page::query()->firstOrNew(['slug' => $slug]);
        $page->fill([
            'title' => (string) ($definition['title'] ?? Str::title($slug)),
            'content' => Page::buildContentFromParts($parts),
            'block_overrides_json' => $overrides,
            'deployment_meta_json' => [
                'blueprint' => $blueprintSlug,
                'style_pack' => $stylePackSlug,
                'theme_preset' => $themePresetSlug,
                'layout_preset' => $layoutPreset,
                'generated_at' => now()->toIso8601String(),
            ],
            'layout_mode' => $layoutMode,
            'is_active' => $activatePages ? true : ($page->exists ? $page->is_active : false),
        ]);

        if (! $page->exists) {
            $page->uuid = (string) Str::uuid();
        }

        $page->save();

        return $page;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pageDefinitions(array $blueprint): array
    {
        $pages = is_array($blueprint['pages'] ?? null) ? $blueprint['pages'] : [];
        $landings = is_array($blueprint['landing_pages'] ?? null) ? $blueprint['landing_pages'] : [];

        return array_merge(array_values($pages), array_values($landings));
    }
}
