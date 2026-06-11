<?php

namespace App\Support;

use App\Models\User;
use App\ModuleAccess;

/**
 * Nested primary-sidebar navigation for all admin modules.
 */
final class ModuleSidebarNavigation
{
    /**
     * @return list<string>
     */
    public static function nestedModuleKeys(): array
    {
        return [
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

    public static function hasNestedNavigation(string $navKey): bool
    {
        return in_array($navKey, self::nestedModuleKeys(), true);
    }

    /**
     * @return array{label: string, icon: string, homeRoute: string}
     */
    public static function meta(string $navKey): array
    {
        $navigation = ModuleAccess::navigation();
        $supplemental = AdminNavigation::supplementalTopLevel();

        if (isset($supplemental[$navKey])) {
            return [
                'label' => (string) $supplemental[$navKey]['label'],
                'icon' => (string) $supplemental[$navKey]['icon'],
                'homeRoute' => (string) $supplemental[$navKey]['route'],
            ];
        }

        $meta = $navigation[$navKey] ?? null;
        if (! is_array($meta)) {
            return ['label' => $navKey, 'icon' => 'circle', 'homeRoute' => 'dashboard'];
        }

        return [
            'label' => (string) ($meta['label'] ?? $navKey),
            'icon' => (string) ($meta['icon'] ?? 'circle'),
            'homeRoute' => (string) ($meta['route'] ?? 'dashboard'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function defaultExpanded(string $navKey): array
    {
        return match ($navKey) {
            ModuleAccess::SITE_ARCHITECT => ['content', 'building'],
            ModuleAccess::OPERATIONS => ['hiring', 'coverage', 'catalog'],
            ModuleAccess::MARKETING => ['dashboard', 'intelligence'],
            ModuleAccess::GROWTH_CENTER => ['intelligence', 'organic'],
            ModuleAccess::USER_MANAGEMENT => ['directory'],
            ModuleAccess::SECURITY => ['monitoring'],
            ModuleAccess::SYSTEM => ['platform'],
            ModuleAccess::SETTINGS => ['brand'],
            default => [],
        };
    }

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     items: list<array{route: string, label: string, active: bool, legacy?: bool, query?: array<string, string>, fragment?: string}>
     * }>
     */
    public static function sidebarGroups(string $navKey, ?User $user = null): array
    {
        return match ($navKey) {
            ModuleAccess::SITE_ARCHITECT => SiteArchitectNavigation::sidebarGroups($user),
            ModuleAccess::OPERATIONS => self::operationsGroups(),
            ModuleAccess::MARKETING => self::marketingGroups(),
            ModuleAccess::GROWTH_CENTER => self::growthCenterGroups(),
            ModuleAccess::USER_MANAGEMENT => self::userManagementGroups($user),
            ModuleAccess::SECURITY => self::securityGroups(),
            ModuleAccess::SYSTEM => self::systemGroups(),
            ModuleAccess::SETTINGS => self::settingsGroups($user),
            default => [],
        };
    }

    /**
     * @return list<array{key: string, label: string, items: list<array{route: string, label: string, active: bool, query?: array<string, string>, fragment?: string}>}>
     */
    private static function operationsGroups(): array
    {
        return [
            [
                'key' => 'hiring',
                'label' => __('Hiring'),
                'items' => [
                    self::item('operations.job-portal.overview', __('Overview'), request()->routeIs('operations.job-portal.overview')),
                    self::item('operations.job-portal.vacancies.index', __('Vacancies'), request()->routeIs('operations.job-portal.vacancies.*')),
                    self::item('operations.job-portal.applications.index', __('Applications'), request()->routeIs('operations.job-portal.applications.*')),
                ],
            ],
            [
                'key' => 'coverage',
                'label' => __('Coverage'),
                'items' => [
                    self::item('operations.pin-codes.overview', __('Overview'), request()->routeIs('operations.pin-codes.overview')),
                    self::item('operations.pin-codes.directory', __('Directory'), request()->routeIs('operations.pin-codes.directory', 'operations.pin-codes.create', 'operations.pin-codes.edit')),
                    self::item('operations.pin-codes.bulk-import', __('Bulk Import'), request()->routeIs('operations.pin-codes.bulk-import', 'operations.pin-codes.bulk-import.preview', 'operations.pin-codes.bulk-import.confirm', 'operations.pin-codes.bulk-import.cancel')),
                ],
            ],
            [
                'key' => 'catalog',
                'label' => __('Catalog'),
                'items' => [
                    self::item('operations.services.index', __('Services'), request()->routeIs('operations.services.*')),
                    self::item('operations.service-categories.index', __('Categories'), request()->routeIs('operations.service-categories.*')),
                    self::item('operations.bookings.index', __('Bookings'), request()->routeIs('operations.bookings.*')),
                    self::item('operations.admissions.index', __('Admissions'), request()->routeIs('operations.admissions.*')),
                    self::item('operations.revenue-events.index', __('Revenue'), request()->routeIs('operations.revenue-events.*')),
                ],
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, items: list<array{route: string, label: string, active: bool, query?: array<string, string>}>}>
     */
    private static function marketingGroups(): array
    {
        return [
            [
                'key' => 'dashboard',
                'label' => __('Dashboard'),
                'items' => [
                    self::item('marketing.dashboard', __('Overview'), self::marketingDashboardTabActive('overview'), query: ['tab' => 'overview']),
                    self::item('marketing.dashboard', __('Google Ads'), self::marketingDashboardTabActive('google-ads'), query: ['tab' => 'google-ads']),
                    self::item('marketing.dashboard', __('Meta Ads'), self::marketingDashboardTabActive('meta'), query: ['tab' => 'meta']),
                    self::item('marketing.dashboard', __('Communication'), self::marketingDashboardTabActive('communication'), query: ['tab' => 'communication']),
                    self::item('marketing.dashboard', __('Campaigns'), self::marketingDashboardTabActive('campaigns'), query: ['tab' => 'campaigns']),
                    self::item('marketing.dashboard', __('Insights'), self::marketingDashboardTabActive('insights'), query: ['tab' => 'insights']),
                    self::item('marketing.dashboard', __('Lead Intent'), self::marketingDashboardTabActive('lead-intent'), query: ['tab' => 'lead-intent']),
                ],
            ],
            [
                'key' => 'intelligence',
                'label' => __('Intelligence'),
                'items' => [
                    self::item('marketing.intelligence', __('Executive'), self::marketingIntelligenceTabActive('executive'), query: ['tab' => 'executive']),
                    self::item('marketing.intelligence', __('WhatsApp'), self::marketingIntelligenceTabActive('whatsapp'), query: ['tab' => 'whatsapp']),
                    self::item('marketing.intelligence', __('Calls'), self::marketingIntelligenceTabActive('calls'), query: ['tab' => 'calls']),
                    self::item('marketing.intelligence', __('Attribution'), self::marketingIntelligenceTabActive('attribution'), query: ['tab' => 'attribution']),
                    self::item('marketing.intelligence', __('Conversions'), self::marketingIntelligenceTabActive('conversions'), query: ['tab' => 'conversions']),
                    self::item('marketing.intelligence', __('Reporting'), self::marketingIntelligenceTabActive('reporting'), query: ['tab' => 'reporting']),
                ],
            ],
        ];
    }

    private static function marketingDashboardTabActive(string $tab): bool
    {
        if (request()->routeIs('marketing.campaigns')) {
            return $tab === 'campaigns';
        }

        if (! request()->routeIs('marketing.dashboard')) {
            return false;
        }

        return self::marketingDashboardTabFromRequest() === $tab;
    }

    private static function marketingIntelligenceTabActive(string $tab): bool
    {
        if (request()->routeIs('marketing.attribution')) {
            return $tab === 'attribution';
        }

        if (request()->routeIs('marketing.reports', 'modules.marketing.reports.*')) {
            return $tab === 'reporting';
        }

        if (! request()->routeIs('marketing.intelligence', 'modules.marketing.intelligence')) {
            return false;
        }

        return self::marketingIntelligenceTabFromRequest() === $tab;
    }

    private static function marketingDashboardTabFromRequest(): string
    {
        $tab = (string) request()->query('tab', 'overview');
        $allowed = ['overview', 'google-ads', 'meta', 'communication', 'campaigns', 'insights', 'lead-intent'];

        return in_array($tab, $allowed, true) ? $tab : 'overview';
    }

    private static function marketingIntelligenceTabFromRequest(): string
    {
        $tab = (string) request()->query('tab', 'executive');
        $allowed = ['executive', 'whatsapp', 'calls', 'attribution', 'conversions', 'reporting'];

        return in_array($tab, $allowed, true) ? $tab : 'executive';
    }

    /**
     * @return list<array{key: string, label: string, items: list<array{route: string, label: string, active: bool, query?: array<string, string>}>}>
     */
    private static function growthCenterGroups(): array
    {
        return [
            [
                'key' => 'intelligence',
                'label' => __('Intelligence'),
                'items' => [
                    self::item('growth-center.competitors.index', __('Competitors'), request()->routeIs('growth-center.competitors.index') && request()->query('tab', 'competitors') === 'competitors'),
                    self::item('growth-center.war-room', __('War Room'), request()->routeIs('growth-center.war-room', 'growth-center.war-room.*')),
                    self::item('growth-center.competitors.index', __('Hijack Ops'), request()->query('tab') === 'hijack-opportunities', query: ['tab' => 'hijack-opportunities']),
                ],
            ],
            [
                'key' => 'organic',
                'label' => __('Organic'),
                'items' => [
                    self::item('growth-center.seo.entity', __('SEO'), request()->routeIs('growth-center.seo.*')),
                    self::item('growth-center.aeo.index', __('AEO'), request()->routeIs('growth-center.aeo.*')),
                    self::item('growth-center.geo.location', __('GEO'), request()->routeIs('growth-center.geo.*')),
                ],
            ],
            [
                'key' => 'signals',
                'label' => __('Signals'),
                'items' => [
                    self::item('growth-center.readiness', __('Readiness'), request()->routeIs('growth-center.readiness')),
                    self::item('growth-center.ga4.index', __('GA4'), request()->routeIs('growth-center.ga4.*')),
                    self::item('growth-center.ai-pulse.index', __('AI Pulse'), request()->routeIs('growth-center.ai-pulse.*')),
                ],
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, items: list<array{route: string, label: string, active: bool}>}>
     */
    private static function userManagementGroups(?User $user): array
    {
        $items = [
            self::item('user-management.index', __('Users'), request()->routeIs('user-management.index')),
        ];

        if ($user instanceof User && in_array(strtolower((string) $user->role), ['manager', 'admin', 'super_admin'], true)) {
            $items[] = self::item('user-management.create', __('Add user'), request()->routeIs('user-management.create', 'user-management.store'));
            $items[] = self::item('user-management.index', __('Edit users'), request()->routeIs('user-management.edit', 'user-management.update'));
        }

        return [
            [
                'key' => 'directory',
                'label' => __('Directory'),
                'items' => $items,
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, items: list<array{route: string, label: string, active: bool, fragment?: string}>}>
     */
    private static function securityGroups(): array
    {
        return [
            [
                'key' => 'monitoring',
                'label' => __('Monitoring'),
                'items' => [
                    self::item('modules.security', __('Overview'), request()->routeIs('modules.security'), fragment: 'security-overview'),
                    self::item('modules.security', __('Audit'), false, fragment: 'security-audit'),
                    self::item('modules.security', __('Activity'), false, fragment: 'security-activity'),
                    self::item('modules.security', __('Failed logins'), false, fragment: 'security-failed-logins'),
                    self::item('modules.security', __('Access events'), false, fragment: 'security-access-events'),
                    self::item('modules.security', __('Firewall'), false, fragment: 'security-firewall'),
                ],
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, items: list<array{route: string, label: string, active: bool}>}>
     */
    private static function systemGroups(): array
    {
        return [
            [
                'key' => 'platform',
                'label' => __('Platform'),
                'items' => [
                    self::item('system.overview', __('Overview'), request()->routeIs('system.index', 'system.overview')),
                    self::item('system.source-of-truth', __('Source of Truth'), request()->routeIs('system.source-of-truth')),
                    self::item('settings.integrations', __('Integrations'), request()->routeIs('settings.integrations', 'admin.settings.integrations.*')),
                    self::item('settings.webhooks', __('Webhooks'), request()->routeIs('settings.webhooks')),
                    self::item('system.queue', __('Queue'), request()->routeIs('system.queue')),
                    self::item('system.scheduler', __('Scheduler'), request()->routeIs('system.scheduler')),
                    self::item('system.health', __('Health'), request()->routeIs('system.health')),
                ],
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, items: list<array{route: string, label: string, active: bool}>}>
     */
    private static function settingsGroups(?User $user): array
    {
        $items = [
            self::item('settings.appearance', __('Appearance'), request()->routeIs('settings.appearance', 'settings.appearance.preview.*')),
            self::item('settings.global-content', __('Global Content'), request()->routeIs('settings.global-content')),
        ];

        if ($user instanceof User && BackupOperator::allows($user)) {
            $items[] = self::item('settings.backup', __('Backup'), request()->routeIs('settings.backup', 'settings.system.backup*'));
        }

        if ($user instanceof User && strtolower((string) $user->role) === 'super_admin') {
            $items[] = self::item('settings.maintenance', __('Maintenance'), request()->routeIs('settings.maintenance', 'settings.system.maintenance'));
        }

        return [
            [
                'key' => 'brand',
                'label' => __('Brand & content'),
                'items' => $items,
            ],
        ];
    }

    /**
     * @param  array<string, string>  $query
     * @return array{route: string, label: string, active: bool, legacy?: bool, query?: array<string, string>, fragment?: string}
     */
    private static function item(string $route, string $label, bool $active, array $query = [], ?string $fragment = null, bool $legacy = false): array
    {
        $item = [
            'route' => $route,
            'label' => $label,
            'active' => $active,
        ];

        if ($query !== []) {
            $item['query'] = $query;
        }

        if ($fragment !== null) {
            $item['fragment'] = $fragment;
        }

        if ($legacy) {
            $item['legacy'] = true;
        }

        return $item;
    }
}
