<?php

namespace App\Support;

use App\ModuleAccess;

/**
 * Supplemental admin navigation (sidebar order, access aliases, active route patterns).
 */
final class AdminNavigation
{
    /** @deprecated Use {@see ModuleAccess::SYSTEM} */
    public const string SYSTEM_NAV_KEY = ModuleAccess::SYSTEM;

    /**
     * Top-level sidebar order (keys from {@see ModuleAccess::navigation()} + supplemental).
     *
     * @return list<string>
     */
    public static function sidebarOrder(): array
    {
        return [
            ModuleAccess::DASHBOARD,
            ModuleAccess::SITE_ARCHITECT,
            ModuleAccess::OPERATIONS,
            ModuleAccess::MARKETING,
            ModuleAccess::GROWTH_CENTER,
            ModuleAccess::USER_MANAGEMENT,
            ModuleAccess::SECURITY,
            ModuleAccess::SYSTEM,
            ModuleAccess::SETTINGS,
        ];
    }

    /**
     * Sidebar sections (visual separators between groups).
     *
     * @return list<list<string>>
     */
    public static function sidebarSections(): array
    {
        return [
            [
                ModuleAccess::DASHBOARD,
                ModuleAccess::SITE_ARCHITECT,
                ModuleAccess::OPERATIONS,
            ],
            [
                ModuleAccess::MARKETING,
                ModuleAccess::GROWTH_CENTER,
            ],
            [
                ModuleAccess::USER_MANAGEMENT,
                ModuleAccess::SECURITY,
                ModuleAccess::SYSTEM,
                ModuleAccess::SETTINGS,
            ],
        ];
    }

    /**
     * Module grant required to show a sidebar entry (supplemental keys map to real modules).
     */
    public static function accessModuleKey(string $navKey): string
    {
        return $navKey;
    }

    /**
     * Supplemental top-level sidebar entries not stored in module_access.
     *
     * @return array<string, array{label: string, icon: string, route: string}>
     */
    public static function supplementalTopLevel(): array
    {
        return [];
    }

    /**
     * Whether a nav key is a standard ModuleAccess module.
     */
    public static function isModuleKey(string $navKey): bool
    {
        return ModuleAccess::isValidKey($navKey);
    }

    /**
     * Route patterns used to highlight sidebar items (route name patterns).
     *
     * @return list<string>
     */
    public static function activeRoutePatterns(string $navKey): array
    {
        return match ($navKey) {
            ModuleAccess::OPERATIONS => [
                'modules.operations',
                'operations.*',
            ],
            ModuleAccess::MARKETING => [
                'modules.marketing',
                'modules.marketing.*',
                'marketing.dashboard',
                'marketing.intelligence',
                'marketing.campaigns',
                'marketing.attribution',
                'marketing.reports',
            ],
            ModuleAccess::GROWTH_CENTER => [
                'modules.growth-center',
                'growth-center.*',
                'growth-center.war-room',
            ],
            ModuleAccess::SECURITY => [
                'modules.security',
            ],
            ModuleAccess::SYSTEM => [
                'system.*',
                'settings.integrations',
                'settings.webhooks',
                'admin.settings.integrations.*',
            ],
            ModuleAccess::SETTINGS => [
                'settings.index',
                'settings.appearance',
                'settings.global-content',
                'settings.backup',
                'settings.maintenance',
                'settings.appearance.preview.*',
                'settings.system.*',
            ],
            ModuleAccess::SITE_ARCHITECT => [
                'modules.site-architect',
                'site-architect.*',
            ],
            default => [],
        };
    }

    public static function isNavActive(string $navKey): bool
    {
        foreach (self::activeRoutePatterns($navKey) as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        if (self::isModuleKey($navKey)) {
            $route = ModuleAccess::navigation()[$navKey]['route'] ?? null;

            return is_string($route) && $route !== '' && request()->routeIs($route);
        }

        $supplemental = self::supplementalTopLevel()[$navKey]['route'] ?? null;

        return is_string($supplemental) && $supplemental !== '' && request()->routeIs($supplemental);
    }
}
