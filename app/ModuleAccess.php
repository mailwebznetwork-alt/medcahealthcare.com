<?php

namespace App;

/**
 * Canonical module keys for sidebar, route middleware, and persisted user grants.
 */
final class ModuleAccess
{
    public const string DASHBOARD = 'dashboard';

    public const string SITE_ARCHITECT = 'site_architect';

    public const string OPERATIONS = 'operations';

    public const string MARKETING = 'marketing';

    public const string GROWTH_CENTER = 'growth_center';

    public const string USER_MANAGEMENT = 'user_management';

    public const string SECURITY = 'security';

    public const string SYSTEM = 'system';

    public const string SETTINGS = 'settings';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::DASHBOARD,
            self::SITE_ARCHITECT,
            self::OPERATIONS,
            self::MARKETING,
            self::GROWTH_CENTER,
            self::USER_MANAGEMENT,
            self::SECURITY,
            self::SYSTEM,
            self::SETTINGS,
        ];
    }

    /**
     * Default grants for new users and backfilled accounts (all modules on).
     *
     * @return array<string, bool>
     */
    public static function defaultGrants(): array
    {
        return array_fill_keys(self::keys(), true);
    }

    public static function isValidKey(string $key): bool
    {
        return in_array($key, self::keys(), true);
    }

    /**
     * Sidebar + route metadata (single source of truth).
     *
     * @return array<string, array{label: string, icon: string, route: string|null, children?: list<array{key: string, label: string, icon: string, route: string}>}>
     */
    public static function navigation(): array
    {
        return [
            self::DASHBOARD => [
                'label' => 'Dashboard',
                'icon' => 'layout-dashboard',
                'route' => 'dashboard',
            ],
            self::SITE_ARCHITECT => [
                'label' => 'Site Architect',
                'icon' => 'drafting-compass',
                'route' => 'site-architect.pages.index',
            ],
            self::OPERATIONS => [
                'label' => 'Operations',
                'icon' => 'workflow',
                'route' => 'modules.operations',
            ],
            self::MARKETING => [
                'label' => 'Marketing',
                'icon' => 'megaphone',
                'route' => 'marketing.dashboard',
            ],
            self::GROWTH_CENTER => [
                'label' => 'Growth Center',
                'icon' => 'trending-up',
                'route' => 'modules.growth-center',
            ],
            self::USER_MANAGEMENT => [
                'label' => 'User Management',
                'icon' => 'users-round',
                'route' => 'user-management.index',
            ],
            self::SECURITY => [
                'label' => 'Security',
                'icon' => 'shield-check',
                'route' => 'modules.security',
            ],
            self::SYSTEM => [
                'label' => 'System',
                'icon' => 'server',
                'route' => 'system.index',
            ],
            self::SETTINGS => [
                'label' => 'Settings',
                'icon' => 'settings',
                'route' => 'settings.appearance',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public static function labelsForForm(): array
    {
        return [
            self::DASHBOARD => [
                'label' => 'Dashboard',
                'description' => 'Executive overview and operational shortcuts.',
            ],
            self::SITE_ARCHITECT => [
                'label' => 'Site Architect',
                'description' => 'Structure, services, and experience composition.',
            ],
            self::OPERATIONS => [
                'label' => 'Operations',
                'description' => 'Live run-state, queues, and service health signals.',
            ],
            self::MARKETING => [
                'label' => 'Marketing',
                'description' => 'Acquisition, attribution, and campaign intelligence.',
            ],
            self::GROWTH_CENTER => [
                'label' => 'Growth Center',
                'description' => 'Competitor intelligence, SEO, coverage, GA4, AI Pulse scans, and growth metrics.',
            ],
            self::USER_MANAGEMENT => [
                'label' => 'User Management',
                'description' => 'People, roles, and directory governance.',
            ],
            self::SECURITY => [
                'label' => 'Security',
                'description' => 'Posture, access boundaries, and compliance signals.',
            ],
            self::SYSTEM => [
                'label' => 'System',
                'description' => 'Platform health, queue, scheduler, integrations, and webhooks.',
            ],
            self::SETTINGS => [
                'label' => 'Settings',
                'description' => 'Workspace configuration and preferences.',
            ],
        ];
    }
}
