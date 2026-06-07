<?php

namespace App\Services\Governance;

use App\Enums\PageCategory;
use App\Enums\ServiceVisibility;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single visibility governance model across catalog and pages.
 */
class VisibilityGovernanceService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshotForCategory(ServiceCategory $category): array
    {
        return [
            'entity' => 'category',
            'is_active' => $category->is_active,
            'is_featured' => (bool) $category->is_featured,
            'visibility' => $category->visibility?->value ?? ServiceVisibility::Public->value,
            'show_on_homepage' => (bool) $category->show_on_homepage,
            'show_on_about' => (bool) $category->show_on_about,
            'show_on_contact' => (bool) $category->show_on_contact,
            'category_page_visible' => $category->isListedPublicly(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshotForService(Service $service): array
    {
        return [
            'entity' => 'service',
            'is_active' => $service->is_active,
            'is_featured' => (bool) $service->is_featured,
            'is_top_rated' => (bool) $service->is_top_rated,
            'publish_status' => $service->publish_status?->value,
            'visibility' => $service->visibility?->value,
            'show_on_homepage' => (bool) $service->show_on_homepage,
            'show_on_about' => (bool) $service->show_on_about,
            'show_on_contact' => (bool) $service->show_on_contact,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshotForSubService(SubService $sub): array
    {
        return [
            'entity' => 'sub_service',
            'is_active' => $sub->is_active,
            'is_featured' => (bool) $sub->is_featured,
            'is_top_rated' => (bool) $sub->is_top_rated,
            'visibility' => $sub->visibility?->value,
            'show_on_homepage' => (bool) $sub->show_on_homepage,
            'show_on_about' => (bool) $sub->show_on_about,
            'show_on_contact' => (bool) $sub->show_on_contact,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshotForPage(Page $page): array
    {
        $flags = is_array($page->visibility_flags) ? $page->visibility_flags : [];

        return [
            'entity' => 'page',
            'is_active' => (bool) $page->is_active,
            'page_category' => $page->page_category?->value,
            'show_on_homepage' => (bool) ($flags['show_on_homepage'] ?? false),
            'show_on_about' => (bool) ($flags['show_on_about'] ?? false),
            'show_on_contact' => (bool) ($flags['show_on_contact'] ?? false),
            'show_on_category_index' => (bool) ($flags['show_on_category_index'] ?? false),
            'show_on_landing_index' => (bool) ($flags['show_on_landing_index'] ?? false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshotForLocationPage(ServiceLocationPage $mapping): array
    {
        $mapping->loadMissing(['service', 'page', 'pincode']);

        return [
            'entity' => 'location_page',
            'is_indexable' => $mapping->isPubliclyIndexable(),
            'matrix_visible' => ServiceLocationPage::query()
                ->whereKey($mapping->id)
                ->exists(),
            'service_listed' => $mapping->service?->isListedPublicly() ?? false,
            'page_active' => (bool) ($mapping->page?->is_active),
        ];
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeFeaturedServices(Builder $query): Builder
    {
        return $query->where('is_featured', true)->publicListing();
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeTopRatedServices(Builder $query): Builder
    {
        return $query->where('is_top_rated', true)->publicListing();
    }

    /**
     * @param  Builder<ServiceCategory>  $query
     * @return Builder<ServiceCategory>
     */
    public function scopeFeaturedCategories(Builder $query): Builder
    {
        return $query->active()->ordered()->where('is_featured', true);
    }

    /**
     * @param  Builder<SubService>  $query
     * @return Builder<SubService>
     */
    public function scopeFeaturedSubServices(Builder $query): Builder
    {
        return $query->publicListing()->where('is_featured', true);
    }
}
