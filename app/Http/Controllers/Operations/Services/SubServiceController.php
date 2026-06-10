<?php

namespace App\Http\Controllers\Operations\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\Services\StoreSubServiceRequest;
use App\Http\Requests\Operations\Services\UpdateSubServiceRequest;
use App\Models\Service;
use App\Models\SubService;
use App\Services\Operations\CatalogFormViewData;
use App\Services\Operations\CatalogMasterPersister;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubServiceController extends Controller
{
    public function __construct(
        private readonly CatalogMasterPersister $catalogPersister,
        private readonly CatalogFormViewData $catalogFormViewData,
    ) {}

    public function index(Service $service): View
    {
        $this->authorize('update', $service);

        $subServices = $service->subServices()
            ->with(['seo', 'linkedPage'])
            ->ordered()
            ->get();

        return view('operations.services.sub-services.index', compact('service', 'subServices'));
    }

    public function create(Service $service): View
    {
        $this->authorize('update', $service);

        $subService = new SubService([
            'service_id' => $service->id,
            'is_active' => true,
            'is_featured' => false,
            'is_top_rated' => false,
            'show_on_homepage' => false,
            'show_on_about' => false,
            'show_on_contact' => false,
            'publish_status' => \App\Enums\PublishStatus::Published,
            'visibility' => \App\Enums\ServiceVisibility::Public,
            'sort_order' => 0,
        ]);

        return view('operations.services.sub-services.create', compact('service', 'subService'));
    }

    public function store(StoreSubServiceRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();

        $subService = DB::transaction(function () use ($request, $data, $service): SubService {
            $sub = SubService::query()->create([
                'service_id' => $service->id,
                'sub_service_code' => $data['sub_service_code'],
                'title' => $data['title'],
                'short_summary' => $data['short_summary'] ?? null,
                'description' => $data['description'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active', true),
                'is_featured' => $request->boolean('is_featured', false),
                'is_top_rated' => $request->boolean('is_top_rated', false),
                'show_on_homepage' => $request->boolean('show_on_homepage', false),
                'show_on_about' => $request->boolean('show_on_about', false),
                'show_on_contact' => $request->boolean('show_on_contact', false),
                'publish_status' => $data['publish_status'],
                'visibility' => $data['visibility'],
            ]);

            return $this->catalogPersister->persistSubService($sub, $request, $data);
        });

        return redirect()
            ->route('operations.services.sub-services.edit', [$service, $subService])
            ->with('status', __('Sub-service created.'));
    }

    public function edit(Request $request, Service $service, SubService $subService): View
    {
        $this->ensureChildOfService($service, $subService);
        $this->authorize('update', $subService);

        return view('operations.services.sub-services.edit', array_merge(
            compact('service', 'subService'),
            $this->catalogFormViewData->forSubService($request, $service, $subService),
        ));
    }

    public function update(UpdateSubServiceRequest $request, Service $service, SubService $subService): RedirectResponse
    {
        $this->ensureChildOfService($service, $subService);

        $data = $request->validated();

        DB::transaction(function () use ($request, $data, $subService): void {
            $this->catalogPersister->persistSubService($subService, $request, $data);
        });

        $tab = (string) $request->input('active_tab', $request->query('tab', 'basic'));
        $params = ['service' => $service, 'sub_service' => $subService->fresh()];
        if ($tab !== '' && $tab !== 'basic') {
            $params['tab'] = $tab;
        }

        return redirect()
            ->route('operations.services.sub-services.edit', $params)
            ->with('status', __('Sub-service updated.'));
    }

    public function destroy(Service $service, SubService $subService): RedirectResponse
    {
        $this->ensureChildOfService($service, $subService);
        $this->authorize('delete', $subService);

        app(\App\Services\Operations\SubServiceDeletionService::class)->delete($subService, 'ui');

        return redirect()
            ->route('operations.services.edit', ['service' => $service, 'tab' => 'sub_services'])
            ->with('status', __('Sub-service deleted.'));
    }

    private function ensureChildOfService(Service $service, SubService $subService): void
    {
        abort_unless((int) $subService->service_id === (int) $service->id, 404);
    }
}
