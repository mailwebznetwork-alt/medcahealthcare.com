<?php

namespace App\Http\Controllers\Operations\Services;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\Services\StoreServiceRequest;
use App\Http\Requests\Operations\Services\UpdateServiceRequest;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Services\Operations\ServiceDetailPageProvisioner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        private readonly ServiceDetailPageProvisioner $detailPageProvisioner,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Service::class);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'publish_status' => ['nullable', 'in:draft,published'],
            'active' => ['nullable', 'in:0,1'],
            'featured' => ['nullable', 'in:0,1'],
        ]);

        $query = Service::query()->orderByDesc('updated_at');

        if (! empty($validated['q'])) {
            $term = $validated['q'];
            $query->where(function ($q) use ($term): void {
                $q->where('title', 'like', '%'.$term.'%')
                    ->orWhere('service_code', 'like', '%'.$term.'%');
            });
        }

        if (! empty($validated['publish_status'])) {
            $query->where('publish_status', $validated['publish_status']);
        }

        if (isset($validated['active']) && $validated['active'] !== '') {
            $query->where('is_active', $validated['active'] === '1');
        }

        if (isset($validated['featured']) && $validated['featured'] !== '') {
            $query->where('is_featured', $validated['featured'] === '1');
        }

        /** @var LengthAwarePaginator<int, Service> $services */
        $services = $query->paginate(20)->withQueryString();

        return view('operations.services.index', [
            'services' => $services,
            'filters' => $validated,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Service::class);

        $service = new Service([
            'is_active' => true,
            'is_featured' => false,
            'publish_status' => PublishStatus::Draft,
            'visibility' => ServiceVisibility::Public,
            'quality_score' => 0,
            'sort_order' => 0,
        ]);

        $pinCodes = $this->pinCodesForForm();
        $detailPages = $this->detailPagesForForm();

        $suggestedDetailPageSlug = 'service-{code}';
        $patternDetailPage = null;

        return view('operations.services.create', compact(
            'service',
            'pinCodes',
            'detailPages',
            'suggestedDetailPageSlug',
            'patternDetailPage',
        ));
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $service = DB::transaction(function () use ($request, $data) {
            $service = Service::query()->create([
                'title' => $data['title'],
                'service_code' => $data['service_code'],
                'short_summary' => $data['short_summary'] ?? null,
                'description' => $data['description'] ?? null,
                'procedures' => $data['procedures'] ?? null,
                'specialized_care' => $data['specialized_care'] ?? null,
                'shifts' => $data['shifts'] ?? null,
                'price_range' => $data['price_range'] ?? null,
                'image_alt' => $data['image_alt'] ?? null,
                'target_keywords' => $data['target_keywords'] ?? null,
                'ai_keywords' => $data['ai_keywords'] ?? null,
                'quality_score' => $data['quality_score'] ?? 0,
                'is_active' => $request->boolean('is_active', true),
                'is_featured' => $request->boolean('is_featured', false),
                'publish_status' => $data['publish_status'],
                'visibility' => $data['visibility'],
                'sort_order' => $data['sort_order'] ?? 0,
                'detail_page_id' => $this->normalizeDetailPageId($data['detail_page_id'] ?? null),
                'gallery' => [],
            ]);

            $this->syncMedia($request, $service);
            $service->save();

            $this->syncSeo($service, $data['seo'] ?? []);
            $this->syncFaqs($service, $data['faqs'] ?? []);
            $this->syncSchema($service, $data['schema_type'] ?? null, $data['schema_json'] ?? null);
            $this->syncPincodes($service, $data['pincodes'] ?? []);

            return $service->fresh(['seo', 'faqs', 'schema', 'pincodes']);
        });

        return redirect()
            ->route('operations.services.edit', $service)
            ->with('status', __('Service created.'));
    }

    public function edit(Service $service): View
    {
        $this->authorize('update', $service);

        $service->load(['seo', 'faqs', 'schema', 'pincodes']);
        $pinCodes = $this->pinCodesForForm();
        $detailPages = $this->detailPagesForForm();

        $suggestedDetailPageSlug = $this->detailPageProvisioner->suggestedSlug($service);
        $patternDetailPage = $this->detailPageProvisioner->findPageBySuggestedSlug($service);

        return view('operations.services.edit', compact(
            'service',
            'pinCodes',
            'detailPages',
            'suggestedDetailPageSlug',
            'patternDetailPage',
        ));
    }

    public function storeDetailPage(Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $page = $this->detailPageProvisioner->provision($service);

        return redirect()
            ->route('operations.services.edit', $service)
            ->with('status', __('Detail page created and linked. Slug: :slug — edit blocks in Site Architect → Pages.', ['slug' => $page->slug]));
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $data, $service): void {
            $service->fill([
                'title' => $data['title'],
                'short_summary' => $data['short_summary'] ?? null,
                'description' => $data['description'] ?? null,
                'procedures' => $data['procedures'] ?? null,
                'specialized_care' => $data['specialized_care'] ?? null,
                'shifts' => $data['shifts'] ?? null,
                'price_range' => $data['price_range'] ?? null,
                'image_alt' => $data['image_alt'] ?? null,
                'target_keywords' => $data['target_keywords'] ?? null,
                'ai_keywords' => $data['ai_keywords'] ?? null,
                'quality_score' => $data['quality_score'] ?? 0,
                'is_active' => $request->boolean('is_active', true),
                'is_featured' => $request->boolean('is_featured', false),
                'publish_status' => $data['publish_status'],
                'visibility' => $data['visibility'],
                'sort_order' => $data['sort_order'] ?? 0,
                'detail_page_id' => $this->normalizeDetailPageId($data['detail_page_id'] ?? null),
            ]);

            if ($request->boolean('clear_featured_image')) {
                $this->deletePublicPath($service->featured_image);
                $service->featured_image = null;
            }
            if ($request->boolean('clear_icon')) {
                $this->deletePublicPath($service->icon);
                $service->icon = null;
            }

            $this->syncMedia($request, $service);
            $service->save();

            $this->syncSeo($service, $data['seo'] ?? []);
            $service->faqs()->delete();
            $this->syncFaqs($service, $data['faqs'] ?? []);
            $this->syncSchema($service, $data['schema_type'] ?? null, $data['schema_json'] ?? null);
            $this->syncPincodes($service, $data['pincodes'] ?? []);
        });

        return redirect()
            ->route('operations.services.edit', $service)
            ->with('status', __('Service updated.'));
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);

        DB::transaction(function () use ($service): void {
            $this->deletePublicPath($service->featured_image);
            $this->deletePublicPath($service->icon);
            if (is_array($service->gallery)) {
                foreach ($service->gallery as $path) {
                    $this->deletePublicPath($path);
                }
            }
            $service->delete();
        });

        return redirect()
            ->route('operations.services.index')
            ->with('status', __('Service deleted.'));
    }

    public function duplicate(Service $service): RedirectResponse
    {
        $this->authorize('create', Service::class);

        $service->loadMissing(['seo', 'faqs', 'schema', 'pincodes']);

        $copy = DB::transaction(function () use ($service) {
            $new = $service->replicate();
            $new->service_code = $service->service_code.'_copy_'.time();
            $new->detail_page_id = null;
            $new->publish_status = PublishStatus::Draft;
            $new->featured_image = null;
            $new->icon = null;
            $new->gallery = [];
            $new->save();

            if ($service->seo) {
                $seo = $service->seo->replicate();
                $seo->service_id = $new->id;
                $seo->save();
            }

            foreach ($service->faqs as $faq) {
                $row = $faq->replicate();
                $row->service_id = $new->id;
                $row->save();
            }

            if ($service->schema) {
                $sch = $service->schema->replicate();
                $sch->service_id = $new->id;
                $sch->save();
            }

            $new->pincodes()->sync($service->pincodes->pluck('id')->all());

            return $new;
        });

        return redirect()
            ->route('operations.services.edit', $copy)
            ->with('status', __('Duplicate saved as draft with a new service code.'));
    }

    public function preview(Service $service): View
    {
        $this->authorize('view', $service);

        $service->load(['seo', 'faqs', 'schema', 'pincodes']);

        return view('operations.services.preview', compact('service'));
    }

    /**
     * @return Collection<int, PinCode>
     */
    private function pinCodesForForm()
    {
        return PinCode::query()
            ->orderBy('city')
            ->orderBy('pincode')
            ->get(['id', 'pincode', 'area_name', 'city', 'locality']);
    }

    /**
     * Active CMS pages eligible to act as a service detail layout.
     *
     * @return Collection<int, Page>
     */
    private function detailPagesForForm()
    {
        return Page::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);
    }

    private function normalizeDetailPageId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === '0' || $value === 0) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param  array<string, mixed>  $seoInput
     */
    private function syncSeo(Service $service, array $seoInput): void
    {
        $focus = array_values(array_filter($seoInput['focus_keywords'] ?? [], fn ($v) => is_string($v) && $v !== ''));
        $h2 = array_values(array_filter($seoInput['h2'] ?? [], fn ($v) => is_string($v) && $v !== ''));
        $h3 = array_values(array_filter($seoInput['h3'] ?? [], fn ($v) => is_string($v) && $v !== ''));

        $service->seo()->updateOrCreate(
            ['service_id' => $service->id],
            [
                'meta_title' => $seoInput['meta_title'] ?? null,
                'meta_description' => $seoInput['meta_description'] ?? null,
                'focus_keywords' => $focus !== [] ? $focus : null,
                'h1' => $seoInput['h1'] ?? null,
                'h2' => $h2 !== [] ? $h2 : null,
                'h3' => $h3 !== [] ? $h3 : null,
                'ai_context' => $seoInput['ai_context'] ?? null,
                'search_intent' => $seoInput['search_intent'] ?? null,
            ]
        );
    }

    /**
     * @param  list<array{question?: string, answer?: string}>  $rows
     */
    private function syncFaqs(Service $service, array $rows): void
    {
        foreach ($rows as $row) {
            $q = isset($row['question']) ? trim((string) $row['question']) : '';
            $a = isset($row['answer']) ? trim((string) $row['answer']) : '';
            if ($q === '' && $a === '') {
                continue;
            }
            $service->faqs()->create([
                'question' => $q !== '' ? $q : __('Question'),
                'answer' => $a,
            ]);
        }
    }

    private function syncSchema(Service $service, ?string $schemaType, ?string $schemaJsonRaw): void
    {
        $decoded = null;
        if (is_string($schemaJsonRaw) && $schemaJsonRaw !== '') {
            $decoded = json_decode($schemaJsonRaw, true);
        }

        if (! is_array($decoded)) {
            $decoded = [];
        }

        if (($schemaType === null || $schemaType === '') && $decoded === []) {
            $service->schema()?->delete();

            return;
        }

        $service->schema()->updateOrCreate(
            ['service_id' => $service->id],
            [
                'schema_type' => $schemaType ?: 'Thing',
                'schema_json' => is_array($decoded) ? $decoded : [],
            ]
        );
    }

    /**
     * @param  list<int|string>  $ids
     */
    private function syncPincodes(Service $service, array $ids): void
    {
        $ids = array_values(array_unique(array_map(static fn ($v) => (int) $v, array_filter($ids, fn ($v) => $v !== null && $v !== ''))));
        $service->pincodes()->sync($ids);
    }

    private function syncMedia(Request $request, Service $service): void
    {
        $dir = 'services/'.$service->id;

        if ($request->hasFile('featured_image')) {
            $this->deletePublicPath($service->featured_image);
            $service->featured_image = $request->file('featured_image')->store($dir, 'public');
        }

        if ($request->hasFile('icon')) {
            $this->deletePublicPath($service->icon);
            $service->icon = $request->file('icon')->store($dir, 'public');
        }

        if ($request->hasFile('gallery_files')) {
            $gallery = is_array($service->gallery) ? $service->gallery : [];
            foreach ($request->file('gallery_files') as $file) {
                if ($file === null) {
                    continue;
                }
                $gallery[] = $file->store($dir, 'public');
            }
            $service->gallery = $gallery;
        }
    }

    private function deletePublicPath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
