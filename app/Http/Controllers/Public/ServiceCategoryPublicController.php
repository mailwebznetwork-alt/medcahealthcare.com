<?php

namespace App\Http\Controllers\Public;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\UserLocationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceCategoryPublicController extends Controller
{
    public function __construct(
        private readonly UserLocationService $location,
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

    public function show(Request $request, string $code): View
    {
        $category = ServiceCategory::findActiveByCode($code);

        abort_if($category === null, 404);

        $category->loadMissing(['parent', 'children' => fn ($q) => $q->active()->ordered()]);

        $pincode = $this->location->currentPincode();
        $locationRequired = $pincode === null || $request->attributes->get('services_blocked_until_pincode') === true;

        $servicesQuery = Service::query()
            ->publicListing()
            ->whereHas('categories', fn ($q) => $q->where('service_categories.id', $category->id))
            ->with(['seo', 'categories'])
            ->orderBy('sort_order')
            ->orderBy('title');

        if (! $locationRequired && $pincode !== null) {
            $servicesQuery->forPincode($pincode);
        } elseif ($locationRequired) {
            $servicesQuery->whereRaw('0 = 1');
        }

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
}
