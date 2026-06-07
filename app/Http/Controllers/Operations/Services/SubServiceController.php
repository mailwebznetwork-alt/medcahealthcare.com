<?php

namespace App\Http\Controllers\Operations\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\Services\StoreSubServiceRequest;
use App\Http\Requests\Operations\Services\UpdateSubServiceRequest;
use App\Models\Service;
use App\Models\SubService;
use App\Models\SubServiceFaq;
use App\Models\SubServiceSeo;
use App\Services\Import\ImportSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubServiceController extends Controller
{
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

            $this->syncSeo($sub, is_array($data['seo'] ?? null) ? $data['seo'] : []);
            $this->syncFaqs($sub, is_array($data['faqs'] ?? null) ? $data['faqs'] : []);

            return $sub->fresh(['seo', 'faqs', 'service']);
        });

        return redirect()
            ->route('operations.services.sub-services.edit', [$service, $subService])
            ->with('status', __('Sub-service created.'));
    }

    public function edit(Service $service, SubService $subService): View
    {
        $this->ensureChildOfService($service, $subService);
        $this->authorize('update', $subService);

        $subService->load(['seo', 'faqs', 'linkedPage', 'service']);

        return view('operations.services.sub-services.edit', compact('service', 'subService'));
    }

    public function update(UpdateSubServiceRequest $request, Service $service, SubService $subService): RedirectResponse
    {
        $this->ensureChildOfService($service, $subService);

        $data = $request->validated();

        DB::transaction(function () use ($request, $data, $subService): void {
            $subService->update([
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

            $this->syncSeo($subService, is_array($data['seo'] ?? null) ? $data['seo'] : []);
            $this->syncFaqs($subService, is_array($data['faqs'] ?? null) ? $data['faqs'] : []);
        });

        return redirect()
            ->route('operations.services.sub-services.edit', [$service, $subService->fresh()])
            ->with('status', __('Sub-service updated.'));
    }

    public function destroy(Service $service, SubService $subService): RedirectResponse
    {
        $this->ensureChildOfService($service, $subService);
        $this->authorize('delete', $subService);

        $subService->delete();

        return redirect()
            ->route('operations.services.edit', ['service' => $service, 'tab' => 'sub_services'])
            ->with('status', __('Sub-service deleted.'));
    }

    private function ensureChildOfService(Service $service, SubService $subService): void
    {
        abort_unless((int) $subService->service_id === (int) $service->id, 404);
    }

    /**
     * @param  array<string, mixed>  $seo
     */
    private function syncSeo(SubService $sub, array $seo): void
    {
        $keywords = filled($seo['focus_keywords'] ?? null)
            ? ImportSupport::parseKeywords((string) $seo['focus_keywords'])
            : null;

        $fields = array_filter([
            'meta_title' => $seo['meta_title'] ?? null,
            'meta_description' => $seo['meta_description'] ?? null,
            'h1' => $seo['h1'] ?? null,
            'focus_keywords' => $keywords,
        ], static fn ($v) => $v !== null && $v !== '');

        if ($fields === []) {
            return;
        }

        SubServiceSeo::query()->updateOrCreate(['sub_service_id' => $sub->id], $fields);
    }

    /**
     * @param  list<array{question?: string, answer?: string}>  $faqs
     */
    private function syncFaqs(SubService $sub, array $faqs): void
    {
        $pairs = [];
        foreach ($faqs as $row) {
            $question = trim((string) ($row['question'] ?? ''));
            $answer = trim((string) ($row['answer'] ?? ''));
            if ($question === '' && $answer === '') {
                continue;
            }
            if ($question !== '' && $answer !== '') {
                $pairs[] = ['question' => $question, 'answer' => $answer];
            }
        }

        SubServiceFaq::query()->where('sub_service_id', $sub->id)->delete();

        foreach ($pairs as $i => $pair) {
            SubServiceFaq::query()->create([
                'sub_service_id' => $sub->id,
                'question' => $pair['question'],
                'answer' => $pair['answer'],
                'sort_order' => $i,
            ]);
        }
    }
}
