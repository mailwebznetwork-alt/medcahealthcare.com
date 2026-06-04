<?php

namespace App\Support;

use App\Models\User;
use App\Policies\DeploymentEnginePolicy;

/**
 * Site Architect in-module IA (tabs, labels, role visibility).
 *
 * Routes and backend capabilities are unchanged — this only controls discoverability.
 */
final class SiteArchitectNavigation
{
    public const string LABEL_SECTION_CONTENT = 'Section Content';

    public const string LABEL_BLOCKS_STUDIO = 'Section Content';

    public const string LABEL_BLOCKS_FACTORY = 'Blocks Factory';

    public const string LABEL_TEMPLATES = 'Style Templates';

    public const string LABEL_NAV_GROUP_SECTIONS = 'Sections';

    public const string LABEL_LEGACY_SECTIONS = 'Legacy Sections';

    /**
     * Admin / developer surfaces (Deploy + Advanced).
     */
    public static function showsFullWorkspaceNav(?User $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        return in_array(strtolower((string) $user->role), ['admin', 'super_admin'], true);
    }

    /**
     * Content editor role — simplified page builder (no Factory / Deploy / Advanced).
     */
    public static function isContentEditorRole(?User $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        return strtolower((string) $user->role) === 'editor';
    }

    /**
     * Developer / admin block registry and code tools.
     */
    public static function showsDeveloperBlockTools(?User $user = null): bool
    {
        return self::showsFullWorkspaceNav($user)
            || ! self::isContentEditorRole($user);
    }

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     items: list<array{route: string, label: string, active: bool, legacy?: bool}>
     * }>
     */
    public static function tabGroups(?User $user = null): array
    {
        $user ??= auth()->user();
        $full = self::showsFullWorkspaceNav($user);

        $groups = [
            [
                'key' => 'content',
                'label' => __('Content'),
                'items' => [
                    self::item('site-architect.pages.index', __('Pages'), request()->routeIs('site-architect.pages.*')),
                    self::item('site-architect.blogs.index', __('Blogs'), request()->routeIs('site-architect.blogs.*')),
                    self::item('site-architect.navigation.index', __('Navigation'), request()->routeIs('site-architect.navigation.*')),
                    self::item('site-architect.media.index', __('Media'), request()->routeIs('site-architect.media.*')),
                ],
            ],
            [
                'key' => 'sections',
                'label' => __(self::LABEL_NAV_GROUP_SECTIONS),
                'items' => self::sectionNavItems(),
            ],
        ];

        if ($full) {
            $groups[] = [
                'key' => 'deploy',
                'label' => __('Deploy'),
                'items' => [
                    self::item('site-architect.blueprint-builder.index', __('Blueprint Builder'), request()->routeIs('site-architect.blueprint-builder.*')),
                    self::item('site-architect.deployment-packages.index', __('Packages'), request()->routeIs('site-architect.deployment-packages.*')),
                ],
            ];
            $groups[] = [
                'key' => 'advanced',
                'label' => __('Advanced'),
                'items' => [
                    self::item('site-architect.modules.index', __('Module Builder'), request()->routeIs('site-architect.modules.*')),
                    self::item('site-architect.sections.index', __(self::LABEL_LEGACY_SECTIONS), request()->routeIs('site-architect.section-library.*', 'site-architect.sections.*'), legacy: true),
                ],
            ];
        }

        return $groups;
    }

    /**
     * Compact deploy shortcuts (avoids duplicating primary Deploy tabs).
     *
     * @return list<array{route: string, label: string, hint: string}>
     */
    public static function deploymentShortcutSteps(?User $user = null): array
    {
        $user ??= auth()->user();
        if (! $user instanceof User) {
            return [];
        }

        $steps = [
            ['route' => 'settings.appearance', 'label' => __('Theme & header'), 'hint' => __('Appearance')],
            ['route' => 'settings.global-content', 'label' => __('Global content'), 'hint' => __('Variables')],
        ];

        if (app(DeploymentEnginePolicy::class)->manageBlockPresets($user)) {
            $steps[] = ['route' => 'site-architect.presets.index', 'label' => __(self::LABEL_TEMPLATES), 'hint' => __('Block styles')];
            $steps[] = ['route' => 'site-architect.block-studio.index', 'label' => __(self::LABEL_BLOCKS_STUDIO), 'hint' => __('Content & media')];
        }

        if (self::showsFullWorkspaceNav($user)) {
            $steps[] = ['route' => 'site-architect.blueprint-builder.index', 'label' => __('Blueprint Builder'), 'hint' => __('Generate pages')];
            $steps[] = ['route' => 'site-architect.deployment-packages.index', 'label' => __('Packages'), 'hint' => __('Import / export')];
        }

        return $steps;
    }

    public static function shouldShowDeploymentShortcuts(): bool
    {
        return request()->routeIs(
            'site-architect.blueprint-builder.*',
            'site-architect.deployment-packages.*',
        );
    }

    /**
     * @return list<array{route: string, label: string, active: bool, legacy?: bool}>
     */
    private static function sectionNavItems(): array
    {
        $items = [
            self::item('site-architect.block-studio.index', __(self::LABEL_SECTION_CONTENT), request()->routeIs('site-architect.block-studio.*')),
            self::item('site-architect.presets.index', __(self::LABEL_TEMPLATES), request()->routeIs('site-architect.block-presets.*', 'site-architect.presets.*')),
        ];

        if (self::showsDeveloperBlockTools()) {
            array_splice($items, 1, 0, [
                self::item('site-architect.block-factory.index', __(self::LABEL_BLOCKS_FACTORY), request()->routeIs('site-architect.block-factory.*')),
            ]);
        }

        return $items;
    }

    /**
     * @return array{route: string, label: string, active: bool, legacy?: bool}
     */
    private static function item(string $route, string $label, bool $active, bool $legacy = false): array
    {
        return [
            'route' => $route,
            'label' => $label,
            'active' => $active,
            'legacy' => $legacy,
        ];
    }
}
