<?php

namespace App\Services\Operations;

use App\Enums\PageCategory;
use App\Models\Blog;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;

class PageCategoryResolver
{
    public function resolve(Page $page): PageCategory
    {
        if ($page->page_category instanceof PageCategory) {
            return $page->page_category;
        }

        $slug = (string) $page->slug;

        if (ServiceCategory::query()->where('page_id', $page->id)->exists()) {
            return PageCategory::Category;
        }

        if (\App\Models\SubService::query()->where('page_id', $page->id)->exists()) {
            return PageCategory::SubService;
        }

        if (SubServicePageProvisioner::subCodeFromPageSlug($slug) !== null) {
            return PageCategory::SubService;
        }

        if (CategoryPageProvisioner::categoryCodeFromPageSlug($slug) !== null) {
            return PageCategory::Category;
        }

        if (ServiceLocationPage::query()->where('page_id', $page->id)->exists()) {
            return PageCategory::Location;
        }

        if (Service::query()->where('detail_page_id', $page->id)->exists()) {
            return PageCategory::Service;
        }

        if (ServiceDetailPageProvisioner::serviceCodeFromPageSlug($slug) !== null) {
            return PageCategory::Service;
        }

        $webSlugs = config('services_master.web_page_slugs', []);
        if (in_array($slug, $webSlugs, true)) {
            return PageCategory::Web;
        }

        $blogPrefix = (string) config('services_master.blog_slug_prefix', 'blog');
        if ($slug === $blogPrefix || str_starts_with($slug, $blogPrefix.'-')) {
            return PageCategory::Blog;
        }

        if (Blog::query()->where('slug', $slug)->exists()) {
            return PageCategory::Blog;
        }

        foreach (config('services_master.landing_slug_prefixes', []) as $prefix) {
            if ($prefix !== '' && str_starts_with($slug, (string) $prefix)) {
                return PageCategory::Landing;
            }
        }

        $locationPattern = (string) config('services_master.location_page_slug_pattern', 'service-{code}-loc-{pincode}');
        if (str_contains($locationPattern, '-loc-') && str_contains($slug, '-loc-')) {
            return PageCategory::Location;
        }

        return PageCategory::Other;
    }

    public function applyToPage(Page $page): void
    {
        $category = $this->resolve($page);
        if ($page->page_category !== $category) {
            $page->forceFill(['page_category' => $category])->saveQuietly();
        }
    }
}
