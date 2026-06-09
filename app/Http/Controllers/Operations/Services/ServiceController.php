<?php

namespace App\Http\Controllers\Operations\Services;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Http\Controllers\Concerns\InteractsWithLegacyManagedModules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\Services\StoreServiceRequest;
use App\Http\Requests\Operations\Services\UpdateServiceRequest;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Review;
use App\Models\Service;
use App\ModuleAccess;
use App\Services\DynamicModules\LegacyManagedModuleRegistry;
use App\Repositories\Operations\ServiceCategoryRepository;
use App\Services\Operations\ServiceCategoryService;
use App\Services\Operations\ServiceDetailPageProvisioner;
use App\Services\Operations\ServiceDetailPageSeoSync;
use App\Services\Operations\ServiceGeminiAssistant;
use App\Services\Operations\ServiceLifecycle;
use App\Services\Operations\ServiceMasterOrchestrator;
use App\Services\Operations\ServiceRelatedPageTokens;
use App\Services\Operations\ServiceSeoOwnership;
use App\Services\Public\PagePublicPreviewService;
use App\Services\Public\ServicesDetailPageResolver;
use App\Services\ServiceContextCollector;
use App\Services\SiteArchitect\ServiceInsertCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ServiceController extends Controller
{
    use InteractsWithLegacyManagedModules;

    public function __construct(
        private readonly ServiceDetailPageProvisioner $detailPageProvisioner,
        private readonly ServicesDetailPageResolver $detailPageResolver,
        private readonly PagePublicPreviewService $pagePublicPreview,
        private readonly ServiceDetailPageSeoSync $detailPageSeoSync,
        private readonly ServiceRelatedPageTokens $relatedPageTokens,
        private readonly ServiceInsertCatalog $serviceInsertCatalog,
        private readonly ServiceCategoryRepository $categoryRepository,
        private readonly ServiceCategoryService $categoryService,
        private readonly ServiceMasterOrchestrator $serviceMasterOrchestrator,
        private readonly ServiceGeminiAssistant $serviceGeminiAssistant,
        private readonly ServiceLifecycle $serviceLifecycle,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Service::class);

        return view('operations.services.index');
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

        return view('operations.services.create', array_merge(
            compact(
                'service',
                'pinCodes',
                'detailPages',
                'suggestedDetailPageSlug',
                'patternDetailPage',
            ),
            $this->legacyModuleContext(LegacyManagedModuleRegistry::SERVICES),
            $this->serviceFormViewData($request, $service, null),
        ));
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $service = DB::transaction(function () use ($request, $data) {
            app(\App\Services\Governance\ServiceCreationGuard::class)
                ->resolveForExplicitRecreate((string) $data['service_code'], 'ui');

            $service = Service::query()->create(array_merge([
                'title' => $data['title'],
                'service_code' => $data['service_code'],
                'price_range' => $data['price_range'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'is_featured' => $request->boolean('is_featured', false),
                'publish_status' => $data['publish_status'],
                'visibility' => $data['visibility'],
                'sort_order' => $data['sort_order'] ?? 0,
                'detail_page_id' => null,
            ], $this->contentAttributesFromValidated($data)));

            $this->syncMedia($request, $service);
            $service->save();

            $this->syncPincodes($service, $data['pincodes'] ?? []);
            $this->categoryService->syncServiceCategories($service, $data['category_ids'] ?? []);
            $this->syncSeo($service, is_array($data['seo'] ?? null) ? $data['seo'] : []);
            $this->syncFaqs($service, is_array($data['faqs'] ?? null) ? $data['faqs'] : []);
            $this->syncSchema($service, $data['schema_type'] ?? null, $data['schema_json'] ?? null);

            $this->persistLegacyCustomFields($request, LegacyManagedModuleRegistry::SERVICES, $service);

            $service = $service->fresh(['pincodes', 'seo', 'faqs', 'schema']);
            $this->serviceMasterOrchestrator->sync($service);

            if ($request->boolean('apply_related_to_page')) {
                $this->relatedPageTokens->applyToDetailPage(
                    $service->fresh(),
                    is_array($data['related_service_codes'] ?? null) ? $data['related_service_codes'] : []
                );
            }

            return $service->fresh(['pincodes', 'seo', 'faqs', 'schema', 'detailPage']);
        });

        return $this->redirectAfterServiceSave($request, $service, __('Service created.'));
    }

    public function edit(Service $service): View
    {
        $this->authorize('update', $service);

        $service->load(['pincodes', 'categories', 'seo', 'faqs', 'schema', 'reviews.user', 'detailPage', 'subServices']);
        $pinCodes = $this->pinCodesForForm();
        $detailPages = $this->detailPagesForForm();

        $suggestedDetailPageSlug = $this->detailPageProvisioner->suggestedSlug($service);
        $patternDetailPage = $this->detailPageProvisioner->findPageBySuggestedSlug($service);
        $linkedDetailPage = $this->detailPageResolver->resolveFor($service) ?? $patternDetailPage;

        return view('operations.services.edit', array_merge(
            compact(
                'service',
                'pinCodes',
                'detailPages',
                'suggestedDetailPageSlug',
                'patternDetailPage',
                'linkedDetailPage',
            ),
            $this->legacyModuleContext(LegacyManagedModuleRegistry::SERVICES, $service),
            $this->serviceFormViewData(request(), $service, $linkedDetailPage),
        ));
    }

    public function createDetailPage(Request $request, Service $service): RedirectResponse
    {
        return $this->storeDetailPage($request, $service);
    }

    public function storeDetailPage(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        try {
            $page = $this->detailPageProvisioner->provision($service);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('operations.services.edit', $service)
                ->withErrors(['detail_page' => __('Could not create the detail page: :message', ['message' => $e->getMessage()])]);
        }

        return $this->redirectAfterDetailPageAction($request, $service, $page, __('Detail page created and linked.'));
    }

    public function editDetailPage(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $page = $this->detailPageResolver->resolveFor($service)
            ?? $this->detailPageProvisioner->provision($service);

        $service->loadMissing(['seo', 'faqs', 'schema']);
        $this->detailPageSeoSync->migrateFromServiceIfEmpty($service, $page);

        return $this->redirectAfterDetailPageAction($request, $service, $page, __('Detail page ready.'));
    }

    private function redirectAfterDetailPageAction(Request $request, Service $service, Page $page, string $message): RedirectResponse
    {
        $status = $message.' '.__('Slug: :slug.', ['slug' => $page->slug]);

        if ($request->user()?->hasModuleAccess(ModuleAccess::SITE_ARCHITECT) === true) {
            return redirect()
                ->route('site-architect.pages.index', ['edit' => $page->id])
                ->with('status', $status.' '.__('Edit blocks and SEO below.'));
        }

        return redirect()
            ->route('operations.services.edit', $service)
            ->with('status', $status.' '.__('Open Site Architect → Pages (or ask an admin for Site Architect access) to edit blocks.'));
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $data, $service): void {
            $previousServiceCode = $service->service_code;

            $service->fill(array_merge([
                'title' => $data['title'],
                'service_code' => $data['service_code'],
                'price_range' => $data['price_range'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'is_featured' => $request->boolean('is_featured', false),
                'publish_status' => $data['publish_status'],
                'visibility' => $data['visibility'],
                'sort_order' => $data['sort_order'] ?? 0,
            ], $this->contentAttributesFromValidated($data)));

            $this->syncMedia($request, $service);
            $service->save();

            $this->syncPincodes($service, $data['pincodes'] ?? []);
            $this->categoryService->syncServiceCategories($service, $data['category_ids'] ?? []);
            $this->syncSeo($service, is_array($data['seo'] ?? null) ? $data['seo'] : []);
            $this->syncFaqs($service, is_array($data['faqs'] ?? null) ? $data['faqs'] : []);
            $this->syncSchema($service, $data['schema_type'] ?? null, $data['schema_json'] ?? null);

            $service = $service->fresh(['pincodes', 'seo', 'faqs', 'schema']);
            $this->serviceMasterOrchestrator->sync($service, $previousServiceCode);

            $this->syncReviewModeration($request, $service);
            $this->persistLegacyCustomFields($request, LegacyManagedModuleRegistry::SERVICES, $service);

            if ($request->boolean('apply_related_to_page')) {
                $this->relatedPageTokens->applyToDetailPage(
                    $service->fresh(),
                    is_array($data['related_service_codes'] ?? null) ? $data['related_service_codes'] : []
                );
            }
        });

        return $this->redirectAfterServiceSave($request, $service, __('Service updated.'));
    }

    public function geminiSuggest(Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $suggestions = $this->serviceGeminiAssistant->suggest($service);

        if ($suggestions === null) {
            return $this->redirectAfterServiceSave(
                request(),
                $service,
                __('Gemini suggestions unavailable — check API key or try again.')
            );
        }

        if (filled($suggestions['ai_summary'] ?? null) && ! filled($service->ai_summary)) {
            $service->forceFill(['ai_summary' => $suggestions['ai_summary']])->save();
        }

        $faqRows = $suggestions['faq_suggestions'] ?? [];
        if (is_array($faqRows) && $service->faqs()->count() === 0) {
            foreach ($faqRows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $q = trim((string) ($row['question'] ?? ''));
                $a = trim((string) ($row['answer'] ?? ''));
                if ($q === '' && $a === '') {
                    continue;
                }
                $service->faqs()->create([
                    'question' => $q !== '' ? $q : __('Question'),
                    'answer' => $a,
                ]);
            }
        }

        $this->serviceMasterOrchestrator->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema']));

        return $this->redirectAfterServiceSave(
            request(),
            $service->fresh(),
            __('Gemini suggestions applied where fields were empty. Scores and pages refreshed.')
        );
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);

        $this->serviceLifecycle->delete($service);

        return redirect()
            ->route('operations.services.index')
            ->with('status', __('Service deleted.'));
    }

    public function duplicate(Service $service): RedirectResponse
    {
        $this->authorize('create', Service::class);

        $copy = $this->serviceLifecycle->duplicate($service);

        return redirect()
            ->route('operations.services.edit', $copy)
            ->with('status', __('Duplicate saved as draft with a new service code.'));
    }

    public function preview(Service $service): View
    {
        $this->authorize('view', $service);

        $service->loadMissing(['pincodes', 'seo', 'faqs', 'schema', 'approvedReviews']);

        app(ServiceContextCollector::class)->register($service);

        $detailPage = $this->detailPageResolver->resolveFor($service);
        if ($detailPage !== null) {
            return view(
                'layouts.app',
                $this->pagePublicPreview->viewDataFor($detailPage)
            );
        }

        return view('public.services.show', [
            'service' => $service,
            'averageRating' => $service->averageApprovedRating(),
            'reviewsCount' => $service->approvedReviewsCount(),
        ]);
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
        $service->loadMissing('detailPage');
        $linkedPage = $service->detailPage;
        $pageSeoCanonical = ServiceSeoOwnership::pageSeoOverridesService($linkedPage);

        $focus = array_values(array_filter($seoInput['focus_keywords'] ?? [], fn ($v) => is_string($v) && $v !== ''));
        $h2 = array_values(array_filter($seoInput['h2'] ?? [], fn ($v) => is_string($v) && $v !== ''));
        $h3 = array_values(array_filter($seoInput['h3'] ?? [], fn ($v) => is_string($v) && $v !== ''));

        $payload = [
            'ai_context' => $seoInput['ai_context'] ?? null,
            'search_intent' => $seoInput['search_intent'] ?? null,
        ];

        $secondary = array_values(array_filter($seoInput['secondary_keywords'] ?? [], fn ($v) => is_string($v) && $v !== ''));

        if (! $pageSeoCanonical) {
            $payload['meta_title'] = $seoInput['meta_title'] ?? null;
            $payload['meta_description'] = $seoInput['meta_description'] ?? null;
            $payload['focus_keywords'] = $focus !== [] ? $focus : null;
            $payload['secondary_keywords'] = $secondary !== [] ? $secondary : null;
            $payload['h1'] = $seoInput['h1'] ?? null;
            $payload['h2'] = $h2 !== [] ? $h2 : null;
            $payload['h3'] = $h3 !== [] ? $h3 : null;
            $payload['canonical_url'] = $seoInput['canonical_url'] ?? $service->publicUrl();
            $payload['robots_index'] = array_key_exists('robots_index', $seoInput)
                ? (bool) $seoInput['robots_index']
                : true;
            $payload['og_title'] = $seoInput['og_title'] ?? null;
            $payload['og_description'] = $seoInput['og_description'] ?? null;
            $payload['og_image'] = $seoInput['og_image'] ?? null;
            $payload['twitter_card'] = $seoInput['twitter_card'] ?? 'summary_large_image';
            $payload['entity_tags'] = $this->nullableKeywordArray($seoInput['entity_tags'] ?? null);
            $payload['geo_entities'] = $this->nullableKeywordArray($seoInput['geo_entities'] ?? null);
        }

        $service->seo()->updateOrCreate(
            ['service_id' => $service->id],
            $payload
        );
    }

    /**
     * @param  list<array{question?: string, answer?: string}>  $rows
     */
    private function syncFaqs(Service $service, array $rows): void
    {
        $service->faqs()->delete();

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

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
        $ids = app(\App\Services\Governance\PinCodeCreationGuard::class)->filterEligiblePinIdsForSync($ids);

        $previousPinIds = $service->pincodes()->pluck('pin_codes.id')->all();
        $mappingProtection = app(\App\Services\Governance\MappingProtectionService::class);
        $mappingProtection->recordRemovalsFromSyncDiff($service, $previousPinIds, $ids, 'ui');
        $ids = $mappingProtection->filterAttachablePinIds($service, $ids, 'ui');

        $service->pincodes()->sync($ids);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function contentAttributesFromValidated(array $data): array
    {
        return [
            'short_summary' => $data['short_summary'] ?? null,
            'description' => $data['description'] ?? null,
            'key_benefits' => $this->nullableLinesArray($data['key_benefits'] ?? null),
            'eligibility' => $this->nullableLinesArray($data['eligibility'] ?? null),
            'process_steps' => $this->nullableLinesArray($data['process_steps'] ?? null),
            'ai_summary' => $data['ai_summary'] ?? null,
            'procedures' => $data['procedures'] ?? null,
            'specialized_care' => $data['specialized_care'] ?? null,
            'shifts' => $data['shifts'] ?? null,
            'image_alt' => $data['image_alt'] ?? ($data['featured_image_meta']['alt'] ?? null),
            'featured_image_meta' => is_array($data['featured_image_meta'] ?? null) ? $data['featured_image_meta'] : null,
            'gallery_meta' => is_array($data['gallery_meta'] ?? null) ? $data['gallery_meta'] : null,
            'trust_signals' => is_array($data['trust_signals'] ?? null) ? $data['trust_signals'] : null,
            'target_keywords' => $this->nullableKeywordArray($data['target_keywords'] ?? null),
            'ai_keywords' => $this->nullableKeywordArray($data['ai_keywords'] ?? null),
        ];
    }

    /**
     * @param  mixed  $value
     * @return list<string>|null
     */
    private function nullableLinesArray(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $items = array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $value
        ), static fn (string $item): bool => $item !== ''));

        return $items === [] ? null : $items;
    }

    /**
     * @param  mixed  $value
     * @return list<string>|null
     */
    private function nullableKeywordArray(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $items = array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $value
        ), static fn (string $item): bool => $item !== ''));

        return $items === [] ? null : $items;
    }

    private function syncMedia(Request $request, Service $service): void
    {
        $attacher = app(\App\Services\Media\ServiceMediaAttacher::class);
        $userId = $request->user()?->id;

        $removeGallery = $request->input('remove_gallery', []);
        if (is_array($removeGallery) && $removeGallery !== []) {
            foreach ($removeGallery as $path) {
                if (! is_string($path) || $path === '') {
                    continue;
                }
                if (str_starts_with($path, 'services/')) {
                    $this->deletePublicPath($path);
                }
                $attacher->removeGalleryPath($service, $path);
            }
        }

        if ($request->hasFile('featured_image')) {
            if (is_string($service->featured_image) && str_starts_with($service->featured_image, 'services/')) {
                $this->deletePublicPath($service->featured_image);
            }
            $attacher->attachFeatured($service, $request->file('featured_image'), $userId);
        } elseif ($request->filled('featured_media_id') && ! $request->hasFile('featured_image')) {
            $attacher->attachFeaturedById($service, (int) $request->input('featured_media_id'));
        }

        if ($request->hasFile('icon')) {
            if (is_string($service->icon) && str_starts_with($service->icon, 'services/')) {
                $this->deletePublicPath($service->icon);
            }
            $attacher->attachIcon($service, $request->file('icon'), $userId);
        } elseif ($request->filled('icon_media_id') && ! $request->hasFile('icon')) {
            $attacher->attachIconById($service, (int) $request->input('icon_media_id'));
        }

        $pickerGallery = $request->input('picker_gallery_media_ids', []);
        if (is_array($pickerGallery)) {
            foreach ($pickerGallery as $mediaId) {
                if (is_numeric($mediaId) && (int) $mediaId > 0) {
                    $attacher->attachGalleryById($service, (int) $mediaId);
                }
            }
        }

        if ($request->hasFile('gallery_files')) {
            foreach ($request->file('gallery_files') as $file) {
                if ($file === null) {
                    continue;
                }
                $attacher->attachGalleryItem($service, $file, $userId);
            }
        }

        if ($service->featured_media_id) {
            $media = \App\Models\Media::query()->find($service->featured_media_id);
            if ($media) {
                $meta = is_array($service->featured_image_meta) ? $service->featured_image_meta : [];
                $media->update(array_filter([
                    'alt_text' => $meta['alt'] ?? $service->image_alt,
                    'title' => $meta['title'] ?? null,
                    'caption' => $meta['caption'] ?? null,
                    'description' => $meta['description'] ?? null,
                ], static fn (mixed $v): bool => $v !== null && $v !== ''));
                app(\App\Services\Media\MediaImageSeoScorer::class)->persist($media);
            }
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

    /**
     * @return array<string, mixed>
     */
    private function serviceFormViewData(Request $request, Service $service, ?Page $linkedDetailPage): array
    {
        $pageContent = $linkedDetailPage?->content ?? $this->detailPageProvisioner->findPageBySuggestedSlug($service)?->content;
        $relatedFromPage = $this->relatedPageTokens->codesFromPageContent(
            $pageContent,
            strtolower((string) $service->service_code)
        );

        $selectedRelated = old('related_service_codes', $relatedFromPage);

        $service->loadMissing(['seo', 'locationPages']);
        $optimization = $service->optimization_snapshot['scores'] ?? [
            'seo' => $service->seo?->seo_score ?? 0,
            'aeo' => $service->seo?->aeo_score ?? 0,
            'geo' => $service->seo?->geo_score ?? 0,
            'schema' => $service->seo?->schema_health_score ?? 0,
            'content' => $service->seo?->content_quality_score ?? 0,
            'local' => $service->seo?->local_seo_score ?? 0,
            'image' => $service->seo?->image_seo_score ?? 0,
            'ai_discovery' => $service->seo?->ai_discovery_score ?? 0,
        ];
        $recommendations = $service->seo?->seo_recommendations
            ?? ($service->optimization_snapshot['recommendations'] ?? []);

        return [
            'linkedDetailPage' => $linkedDetailPage,
            'optimizationScores' => $optimization,
            'seoRecommendations' => is_array($recommendations) ? $recommendations : [],
            'locationPageCount' => $service->locationPages()->count(),
            'activeTab' => (string) $request->query('tab', old('active_tab', 'basic')),
            'categoryOptions' => $this->categoryRepository->activeForPicker(),
            'serviceCatalog' => $this->serviceInsertCatalog->forDropdown()
                ->filter(static fn (Service $row): bool => ! $service->exists || (int) $row->id !== (int) $service->id)
                ->values(),
            'selectedRelatedCodes' => is_array($selectedRelated) ? $selectedRelated : [],
            'serviceReviews' => $service->exists
                ? $service->reviews()->with('user:id,name,email')->latest()->get()
                : collect(),
            'subServices' => $service->exists
                ? $service->subServices()->ordered()->get()
                : collect(),
        ];
    }

    private function redirectAfterServiceSave(Request $request, Service $service, string $message): RedirectResponse
    {
        $tab = (string) $request->input('active_tab', $request->query('tab', 'basic'));
        $params = ['service' => $service];
        if ($tab !== '' && $tab !== 'basic') {
            $params['tab'] = $tab;
        }

        return redirect()
            ->route('operations.services.edit', $params)
            ->with('status', $message);
    }

    private function syncReviewModeration(Request $request, Service $service): void
    {
        if (! $request->user()?->can('moderate', Review::class)) {
            return;
        }

        $rows = $request->input('review_moderation', []);
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = isset($row['id']) ? (int) $row['id'] : 0;
            $status = isset($row['status']) ? (string) $row['status'] : '';
            if ($id <= 0 || ! in_array($status, [Review::STATUS_PENDING, Review::STATUS_APPROVED, Review::STATUS_REJECTED], true)) {
                continue;
            }

            Review::query()
                ->where('service_id', $service->id)
                ->whereKey($id)
                ->update(['status' => $status]);
        }
    }
}
