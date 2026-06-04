<?php

namespace App\Support;

/**
 * User-facing Site Architect copy (SAFE UX layer — no logic changes).
 */
final class SiteArchitectUxCopy
{
    public static function workspaceWelcome(): string
    {
        return __('Build and publish marketing pages. Most daily work happens under Content → Pages.');
    }

    /**
     * @return list<array{step: string, title: string, hint: string, route?: string}>
     */
    public static function composeJourneySteps(): array
    {
        return [
            [
                'step' => '1',
                'title' => __('Create or open a page'),
                'hint' => __('Content → Pages. Set title, slug, and turn Live on when ready.'),
                'route' => 'site-architect.pages.index',
            ],
            [
                'step' => '2',
                'title' => __('Add sections (blocks)'),
                'hint' => __('Use “Add existing block” and pick a slug such as hero-home or near-you-home.'),
                'route' => 'site-architect.pages.index',
            ],
            [
                'step' => '3',
                'title' => __('Edit wording & images'),
                'hint' => __('Blocks Studio — pick the same block slug and edit Content / Media. No code required for managed blocks.'),
                'route' => 'site-architect.block-studio.index',
            ],
            [
                'step' => '4',
                'title' => __('Preview'),
                'hint' => __('Use the preview panel on the page form or Open full preview — matches the public site.'),
                'route' => 'site-architect.pages.index',
            ],
            [
                'step' => '5',
                'title' => __('Publish'),
                'hint' => __('Save page + enable Live. Header links: Content → Navigation.'),
                'route' => 'site-architect.navigation.index',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function tabGroupHints(): array
    {
        return [
            'content' => __('Pages, blog posts, menus, and uploads — start here.'),
            'sections' => __('Edit section wording and images. Style templates are optional saved looks.'),
            'blocks' => __('Edit section wording and images. Style templates are optional saved looks.'),
            'deploy' => __('Generate or import whole sites — admin setup, not daily editing.'),
            'advanced' => __('Custom data types and legacy section tokens — backward compatibility only.'),
        ];
    }

    /**
     * @return array<string, array{system: string, user: string, naming_ok: bool, workflow: string}>
     */
    public static function mentalModelMatrix(): array
    {
        return [
            'pages' => [
                'system' => __('Page composer storing ordered {{block:slug}} tokens'),
                'user' => __('WordPress “Pages” — build the site page by page'),
                'naming_ok' => true,
                'workflow' => __('Strong if user ignores raw tokens and uses Add existing block + Studio'),
            ],
            'blocks_studio' => [
                'system' => __('Edits blocks.settings_json (content, media, section, style)'),
                'user' => __('“Edit section text and images”'),
                'naming_ok' => true,
                'workflow' => __('Good when reached from Pages; weak when discovered cold in Blocks tab'),
            ],
            'blocks_factory' => [
                'system' => __('CRUD for block registry + Blade code'),
                'user' => __('Unclear — sounds like Studio; actually developer registry'),
                'naming_ok' => false,
                'workflow' => __('Should be rare for marketing users; friction when modal pushes code'),
            ],
            'templates' => [
                'system' => __('Saved style presets applied to blocks (block_presets)'),
                'user' => __('Theme templates or page templates'),
                'naming_ok' => true,
                'workflow' => __('Optional power feature; not needed for basic publish loop'),
            ],
            'modules' => [
                'system' => __('Dynamic module records + {{module:}} tokens'),
                'user' => __('Plugins or forms'),
                'naming_ok' => false,
                'workflow' => __('Hidden from editors — appropriate'),
            ],
            'blueprint_builder' => [
                'system' => __('Industry blueprint → bulk Page generation'),
                'user' => __('Site wizard / installer'),
                'naming_ok' => true,
                'workflow' => __('Admin-only; correct to hide from editors'),
            ],
            'packages' => [
                'system' => __('Import/export deployment packages'),
                'user' => __('Zip backup?'),
                'naming_ok' => false,
                'workflow' => __('Admin-only'),
            ],
            'legacy_sections' => [
                'system' => __('Deprecated {{section:}} groups'),
                'user' => __('Page sections'),
                'naming_ok' => true,
                'workflow' => __('Correctly labeled legacy; parser still works'),
            ],
        ];
    }
}
