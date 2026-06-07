<?php

namespace App\Services\Governance;

use App\Enums\PageCategory;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use App\Services\Seo\SeoOwnershipGuard;

/**
 * Defines canonical ownership per page type — prevents future conflicts.
 */
class PageOwnershipResolver
{
    /**
     * @return array{owner: string, seo_owner: string, schema_owner: string, visibility_owner: string, editable_in_site_architect: bool}
     */
    public function resolveForPage(Page $page): array
    {
        $category = $page->page_category;

        return match ($category) {
            PageCategory::Category => $this->categoryOwnership(),
            PageCategory::Service => $this->serviceOwnership(),
            PageCategory::SubService => $this->subServiceOwnership(),
            PageCategory::Location => $this->locationOwnership(),
            PageCategory::Web, PageCategory::Landing, PageCategory::Blog => $this->siteArchitectOwnership(),
            default => $this->siteArchitectOwnership(),
        };
    }

    /**
     * @return array{owner: string, seo_owner: string, schema_owner: string, visibility_owner: string, editable_in_site_architect: bool}
     */
    public function categoryOwnership(): array
    {
        return [
            'owner' => 'operations_category',
            'seo_owner' => 'service_category_seo',
            'schema_owner' => 'service_category_schema + CategoryJsonLdBuilder',
            'visibility_owner' => 'service_categories + VisibilityGovernanceService',
            'editable_in_site_architect' => true,
        ];
    }

    /**
     * @return array{owner: string, seo_owner: string, schema_owner: string, visibility_owner: string, editable_in_site_architect: bool}
     */
    public function serviceOwnership(): array
    {
        return [
            'owner' => 'operations_service',
            'seo_owner' => SeoOwnershipGuard::canonicalSourceForService(),
            'schema_owner' => SeoOwnershipGuard::generatedSchemaSource(),
            'visibility_owner' => 'services + VisibilityGovernanceService',
            'editable_in_site_architect' => true,
        ];
    }

    /**
     * @return array{owner: string, seo_owner: string, schema_owner: string, visibility_owner: string, editable_in_site_architect: bool}
     */
    public function subServiceOwnership(): array
    {
        return [
            'owner' => 'operations_sub_service',
            'seo_owner' => 'sub_service_seo',
            'schema_owner' => 'sub_service_schema + parent service graph',
            'visibility_owner' => 'sub_services + VisibilityGovernanceService',
            'editable_in_site_architect' => true,
        ];
    }

    /**
     * @return array{owner: string, seo_owner: string, schema_owner: string, visibility_owner: string, editable_in_site_architect: bool}
     */
    public function locationOwnership(): array
    {
        return [
            'owner' => 'operations_location_matrix',
            'seo_owner' => 'pages + pin_codes enrichment',
            'schema_owner' => SeoOwnershipGuard::generatedSchemaSource(),
            'visibility_owner' => 'service_pincodes + service_location_pages',
            'editable_in_site_architect' => true,
        ];
    }

    /**
     * @return array{owner: string, seo_owner: string, schema_owner: string, visibility_owner: string, editable_in_site_architect: bool}
     */
    public function siteArchitectOwnership(): array
    {
        return [
            'owner' => 'site_architect',
            'seo_owner' => 'pages',
            'schema_owner' => 'pages.schema_json',
            'visibility_owner' => 'pages.visibility_flags + is_active',
            'editable_in_site_architect' => true,
        ];
    }

    /**
     * @return array{owner: string, seo_owner: string, schema_owner: string, visibility_owner: string, editable_in_site_architect: bool}
     */
    public function resolveForEntity(string $entityType, ?int $entityId = null): array
    {
        return match ($entityType) {
            'category' => $this->categoryOwnership(),
            'service' => $this->serviceOwnership(),
            'sub_service' => $this->subServiceOwnership(),
            'location' => $this->locationOwnership(),
            default => $this->siteArchitectOwnership(),
        };
    }
}
