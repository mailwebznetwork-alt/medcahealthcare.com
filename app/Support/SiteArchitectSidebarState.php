<?php

namespace App\Support;

/**
 * Sidebar group data for Site Architect workspace (delegates to {@see SiteArchitectNavigation}).
 */
final class SiteArchitectSidebarState
{
    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     items: list<array{route: string, label: string, active: bool, legacy?: bool, query?: array<string, string>, fragment?: string}>
     * }>
     */
    public static function groups(): array
    {
        return SiteArchitectNavigation::sidebarGroups();
    }

    /**
     * Default expanded group keys for first visit.
     *
     * @return list<string>
     */
    public static function defaultExpanded(): array
    {
        return ['content', 'sections'];
    }
}
