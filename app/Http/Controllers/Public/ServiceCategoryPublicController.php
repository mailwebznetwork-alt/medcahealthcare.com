<?php

namespace App\Http\Controllers\Public;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Discovery\RelatedContentEngine;
use App\Services\Operations\CategoryPageProvisioner;
use App\Services\Public\PageRenderContextRegistrar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceCategoryPublicController extends Controller
{
    public function __construct(
        private readonly PageRenderContextRegistrar $pageRenderContext,
        private readonly CategoryPageProvisioner $categoryPageProvisioner,
        private readonly RelatedContentEngine $relatedContent,
    ) {}

    public function index(): View
    {
        $categories = ServiceCategory::query()
            ->active()
            ->roots()
            ->with(['children' => fn ($q) => $q->active()->ordered()->withCount([
                'services' => fn ($sq) => $sq->where('is_active', true)
                    ->where('publish_status', PublishStatus::Published)
                    ->where('visibility', ServiceVisibility::Public),
            ])])
            ->withCount(['services' => fn ($sq) => $sq->where('is_active', true)
                ->where('publish_status', PublishStatus::Published)
                ->where('visibility', ServiceVisibility::Public)])
            ->ordered()
            ->get();

        return view('public.service-categories.index', compact('categories'));
    }

    public function show(Request $request, string $code): View|RedirectResponse
    {
        $category = ServiceCategory::findActiveByCode($code);

        if ($category === null && ! str_starts_with($code, 'cat-')) {
            $prefixed = ServiceCategory::findActiveByCode('cat-'.$code);
            if ($prefixed !== null) {
                return redirect()->route('public.service-categories.show', $prefixed->code, 301);
            }
        }

        abort_if($category === null, 404);

        $category->loadMissing(['parent', 'children' => fn ($q) => $q->active()->ordered(), 'seo', 'faqs', 'schema']);

        $pincode = null;
        $locationRequired = false;

        $page = $category->linkedPage ?? $this->categoryPageProvisioner->relinkOwnedPage($category);

        if ($page === null && config('phase2_discovery.auto_sync_category_pages', true)) {
            try {
                $page = $this->categoryPageProvisioner->syncFromCategory($category->fresh());
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($page !== null && $page->is_active) {
            $internalLinks = $this->relatedContent->buildForCategory($category, $pincode);

            $this->pageRenderContext->registerCategoryDetail($page, $category, [
                'breadcrumbs' => $this->categoryBreadcrumbs($category),
                'internalLinks' => $internalLinks,
                'pincode' => $pincode,
                'locationRequired' => $locationRequired,
            ]);

            return view('layouts.app', [
                'page' => $page,
                'category' => $category,
            ]);
        }

        $servicesQuery = Service::query()
            ->publicListing()
            ->whereHas('categories', fn ($q) => $q->where('service_categories.id', $category->id))
            ->with(['seo', 'categories'])
            ->orderBy('sort_order')
            ->orderBy('title');

        $services = $servicesQuery->paginate(12)->withQueryString();

        $siblingCategories = ServiceCategory::query()
            ->active()
            ->when(
                $category->parent_id !== null,
                fn ($q) => $q->where('parent_id', $category->parent_id),
                fn ($q) => $q->whereNull('parent_id')
            )
            ->where('id', '!=', $category->id)
            ->ordered()
            ->get(['id', 'name', 'code', 'description']);

        return view('public.service-categories.show', [
            'category' => $category,
            'services' => $services,
            'pincode' => $pincode,
            'locationRequired' => $locationRequired,
            'siblingCategories' => $siblingCategories,
        ]);
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function categoryBreadcrumbs(ServiceCategory $category): array
    {
        return [
            ['label' => __('Home'), 'url' => url('/')],
            ['label' => __('Categories'), 'url' => url('/service-categories')],
            ['label' => $category->name, 'url' => $category->publicUrl()],
        ];
    }
}
