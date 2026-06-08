<?php

namespace App\Support;

use App\Models\User;

final class AdminMetricLinks
{
    public static function security(string $anchor): string
    {
        return route('modules.security').'#'.$anchor;
    }

    public static function marketingDashboard(?string $tab = null): string
    {
        return $tab
            ? route('marketing.dashboard', ['tab' => $tab])
            : route('marketing.dashboard');
    }

    public static function marketingIntelligence(?string $tab = null): string
    {
        return $tab
            ? route('marketing.intelligence', ['tab' => $tab])
            : route('marketing.intelligence');
    }

    public static function growthCenter(string $tab): string
    {
        return match ($tab) {
            'ga4' => route('growth-center.ga4.index'),
            'readiness' => route('growth-center.readiness'),
            'ai-pulse' => route('growth-center.ai-pulse.index'),
            'seo', 'aeo' => route('growth-center.aeo.index'),
            'war-room' => route('growth-center.war-room'),
            'geo', 'pincodes' => route('growth-center.geo.pincodes'),
            default => route('growth-center.competitors.index', ['tab' => $tab]),
        };
    }

    public static function jobPortalVacancies(?string $workflowStatus = null): string
    {
        return $workflowStatus
            ? route('operations.job-portal.vacancies.index', ['workflow_status' => $workflowStatus])
            : route('operations.job-portal.vacancies.index');
    }

    public static function jobPortalApplications(): string
    {
        return route('operations.job-portal.applications.index');
    }

    public static function pinCodesDirectory(array $query = []): string
    {
        return route('operations.pin-codes.directory', $query);
    }

    public static function siteArchitectPages(array $query = []): string
    {
        return route('site-architect.pages.index', $query);
    }

    public static function systemOverview(): string
    {
        return route('system.overview');
    }

    public static function systemQueue(): string
    {
        return route('system.queue');
    }

    public static function sourceOfTruth(?string $anchor = null): string
    {
        $url = route('system.source-of-truth');

        return $anchor ? $url.'#'.$anchor : $url;
    }

    public static function sourceOfTruthMetric(string $key): string
    {
        return match ($key) {
            'orphan_registry' => self::sourceOfTruth('source-of-truth-orphans'),
            'admin_overrides' => self::sourceOfTruth('source-of-truth-governance'),
            'registry_rows', 'pages', 'synced_pages', 'generated', 'manual', 'planned', 'tombstones', 'protected_pages'
                => self::siteArchitectPages(),
            default => self::sourceOfTruth(),
        };
    }

    public static function userManagement(?User $user = null): string
    {
        return $user
            ? route('user-management.edit', $user)
            : route('user-management.index');
    }

    public static function settingsIntegrations(): string
    {
        return route('settings.integrations');
    }
}
