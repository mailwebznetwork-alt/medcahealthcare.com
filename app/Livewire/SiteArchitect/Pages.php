<?php

namespace App\Livewire\SiteArchitect;

use App\Enums\PageCategory;
use App\Enums\PageLayoutMode;
use App\Models\Block;
use App\Models\Page;
use App\Models\Service;
use App\Models\PageFaq;
use App\Models\PageRevision;
use App\Models\PinCode;
use App\Models\SiteSlugRedirect;
use App\Services\ActivityLogService;
use App\Services\Growth\HijackContentBridgeService;
use App\Services\Growth\HijackStrategyReader;
use App\Services\Integrations\OutboundWebhookDispatcher;
use App\Services\DynamicModules\DynamicModuleInsertCatalog;
use App\Support\BlockContent;
use App\Livewire\Concerns\HandlesArchitectFlexibleSave;
use App\Livewire\Concerns\InteractsWithBulkActions;
use App\Livewire\SiteArchitect\Concerns\InteractsWithPageSectionPicker;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Operations\ServiceDetailPageProvisioner;
use App\Services\SiteArchitect\ServiceInsertCatalog;
use App\Support\ArchitectSaveBypass;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Pages extends Component
{
    use AuthorizesRequests;
    use HandlesArchitectFlexibleSave;
    use InteractsWithBulkActions;
    use InteractsWithPageSectionPicker;
    use WithPagination;

    public function mount(): void
    {
        if (request()->query('create') === '1') {
            $this->startCreate();
        }

        $editId = request()->query('edit');
        if (is_numeric($editId)) {
            $this->startEdit((int) $editId);
        }
    }

    public string $mode = 'list';

    public ?int $editingId = null;

    public string $title = '';

    public string $slug = '';

    public string $page_source = '';

    public bool $is_active = false;

    public string $layout_mode = 'contained';

    public string $meta_title = '';

    public string $meta_description = '';

    public string $keywords = '';

    public string $canonical_url = '';

    public string $robots_meta = '';

    public string $og_image = '';

    public string $og_image_alt = '';

    public string $hreflang_json_input = '';

    public string $entity_tags_input = '';

    public bool $fact_check_verified = false;

    public string $content_reviewed_label = '';

    public string $h1 = '';

    public string $h2 = '';

    public string $h3 = '';

    public string $h4 = '';

    public string $h5 = '';

    public string $h6 = '';

    public string $aeo_question = '';

    public string $aeo_answer = '';

    public string $schema_json_input = '';

    public string $gtm_code = '';

    public string $pixel_code = '';

    /** @var list<string> */
    public array $focusKeywords = [];

    /** @var list<string> */
    public array $headingH2 = [];

    /** @var list<string> */
    public array $headingH3 = [];

    public string $ai_context = '';

    public string $search_intent = '';

    public string $schema_type = '';

    /** @var list<array{question: string, answer: string}> */
    public array $faqRows = [];

    /** @var list<array{type: string, slug: string}> */
    public array $contentParts = [];

    /** @var list<int> */
    public array $selectedPinIds = [];

    /** @var array<int, array{serviceability: bool, delivery_charge: ?string, location_keywords: string}> */
    public array $pinPivot = [];

    public bool $blockModalOpen = false;

    public int $previewRefreshNonce = 0;

    public ?string $blockEditingSlug = null;

    public string $block_name = '';

    public string $block_slug = '';

    public string $block_code = '';

    public string $block_custom_css = '';

    public string $module_choice = '';

    public string $service_choice = '';

    public string $block_module_choice = '';

    public int $serviceCatalogNonce = 0;

    public string $pageSearch = '';

    public string $pageCategoryFilter = 'all';

    public function updatingPageSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPageCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function bulkResourceKey(): string
    {
        return 'site_architect.pages';
    }

    protected function bulkFilteredIdsQuery(): Builder
    {
        $query = Page::query()->latest();

        if (trim($this->pageSearch) !== '') {
            $term = '%'.trim($this->pageSearch).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('title', 'like', $term)
                    ->orWhere('slug', 'like', $term);
            });
        }

        if ($this->pageCategoryFilter !== '' && $this->pageCategoryFilter !== 'all') {
            $category = PageCategory::tryFrom($this->pageCategoryFilter);
            if ($category !== null) {
                $query->where('page_category', $category->value);
            }
        }

        return $query;
    }

    /**
     * @param  list<string|int>  $orderedIndices
     */
    public function syncContentPartsOrder(array $orderedIndices): void
    {
        $reordered = [];
        foreach ($orderedIndices as $raw) {
            $index = (int) $raw;
            if (array_key_exists($index, $this->contentParts)) {
                $reordered[] = $this->contentParts[$index];
            }
        }

        if (count($reordered) !== count($this->contentParts)) {
            return;
        }

        $this->contentParts = $reordered;

        if ($this->editingId !== null) {
            app(ActivityLogService::class)->log(
                'page_sections_reorder',
                'site_architect',
                'Page '.$this->editingId.' sections reordered via drag-and-drop',
            );
        }

        session()->flash('status', __('Section order updated.'));
    }

    public function syncServiceDetailPages(): void
    {
        $this->authorize('viewAny', Page::class);

        $count = 0;
        Service::query()->orderBy('id')->each(function (Service $service) use (&$count): void {
            app(\App\Services\Operations\ServiceMasterOrchestrator::class)->sync($service);
            $count++;
        });

        session()->flash(
            'status',
            __('Services master sync complete for :count service(s) — detail + location pages, schema, and scores.', [
                'count' => $count,
            ])
        );
    }

    public function render()
    {
        $pagesQuery = Page::query()->latest();

        if (trim($this->pageSearch) !== '') {
            $term = '%'.trim($this->pageSearch).'%';
            $pagesQuery->where(function ($q) use ($term): void {
                $q->where('title', 'like', $term)
                    ->orWhere('slug', 'like', $term);
            });
        }

        if ($this->pageCategoryFilter !== '' && $this->pageCategoryFilter !== 'all') {
            $category = PageCategory::tryFrom($this->pageCategoryFilter);
            if ($category !== null) {
                $pagesQuery->where('page_category', $category->value);
            }
        }

        $pages = $pagesQuery->paginate(20);

        /** @var array<int, string> $serviceCodesByPageId */
        $serviceCodesByPageId = Service::query()
            ->whereNotNull('detail_page_id')
            ->pluck('service_code', 'detail_page_id')
            ->all();

        $pinCodes = PinCode::query()
            ->where('is_active', true)
            ->orderBy('city')
            ->orderBy('pincode')
            ->get(['id', 'pincode', 'area_name', 'city']);

        $moduleOptions = app(DynamicModuleInsertCatalog::class)->forDropdown();

        $servicesForInsert = $this->blockModalOpen
            ? app(ServiceInsertCatalog::class)->forDropdown()
            : collect();

        $otherPagesForLinks = collect();
        $revisions = collect();
        if ($this->mode === 'form') {
            $otherPagesForLinks = Page::query()
                ->when($this->editingId !== null, fn ($q) => $q->whereKeyNot($this->editingId))
                ->orderBy('title')
                ->limit(40)
                ->get(['id', 'title', 'slug']);

            if ($this->editingId !== null) {
                $revisions = PageRevision::query()
                    ->where('page_id', $this->editingId)
                    ->latest('created_at')
                    ->with('user:id,name')
                    ->limit(15)
                    ->get();
            }
        }

        $productionPreviewUrl = null;
        if ($this->mode === 'form' && $this->editingId !== null) {
            $editingPage = Page::query()->find($this->editingId);
            if ($editingPage !== null) {
                $locRow = \App\Models\ServiceLocationPage::query()
                    ->where('page_id', $editingPage->id)
                    ->first();
                $productionPreviewUrl = ($locRow !== null && $editingPage->is_active)
                    ? $locRow->publicUrl()
                    : route('site-architect.pages.preview', $editingPage);
            }
        }

        $hasSectionTokens = collect($this->contentParts)
            ->contains(fn (array $part): bool => ($part['type'] ?? '') === 'section');

        $pageCategoryCounts = [];
        foreach (PageCategory::cases() as $category) {
            $pageCategoryCounts[$category->value] = Page::query()
                ->where('page_category', $category->value)
                ->count();
        }

        return view('livewire.site-architect.pages', [
            'pages' => $pages,
            'pageCategoryCounts' => $pageCategoryCounts,
            'serviceCodesByPageId' => $serviceCodesByPageId,
            'pinCodes' => $pinCodes,
            'moduleOptions' => $moduleOptions,
            'servicesForInsert' => $servicesForInsert,
            'serviceCatalogNonce' => $this->serviceCatalogNonce,
            'otherPagesForLinks' => $otherPagesForLinks,
            'revisions' => $revisions,
            'productionPreviewUrl' => $productionPreviewUrl,
            'hasSectionTokens' => $hasSectionTokens,
            'sectionLibraryDeprecated' => (bool) config('platform_composition.section_library_deprecated', true),
            'readabilityHint' => $this->mode === 'form' ? $this->computeReadabilityHint() : null,
            'llmReadiness' => $this->mode === 'form' ? $this->computeLlmReadiness() : null,
            'onPageSeo' => $this->mode === 'form' ? $this->computeOnPageSeoChecklist() : null,
            'hijackStrategiesForPage' => $this->mode === 'form'
                ? app(HijackStrategyReader::class)->forPageKeywords($this->pageFocusKeywords())
                : [],
            'sectionPickerGroups' => $this->sectionPickerOpen ? $this->sectionPickerGroups() : [],
            'sectionPickerCategories' => config('page_builder_sections.picker_categories', []),
            'canUseDeveloperBlockTools' => $this->canUseDeveloperBlockTools(),
        ]);
    }

    protected function currentPageSlugForPicker(): ?string
    {
        $slug = trim($this->slug);

        return $slug !== '' ? $slug : null;
    }

    public function startCreate(): void
    {
        $this->authorize('create', Page::class);

        $this->resetForm();
        $this->mode = 'form';
        $this->editingId = null;
    }

    public function startEdit(int $id): void
    {
        $page = Page::query()->with(['pinCodes', 'faqs'])->findOrFail($id);
        $this->authorize('update', $page);

        $this->resetForm();
        $this->editingId = $id;
        $this->mode = 'form';

        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->page_source = (string) ($page->page_source ?? '');
        $this->is_active = $page->is_active;
        $this->layout_mode = $page->layout_mode?->value ?? PageLayoutMode::Contained->value;
        $this->meta_title = (string) ($page->meta_title ?? '');
        $this->meta_description = (string) ($page->meta_description ?? '');
        $this->keywords = (string) ($page->keywords ?? '');
        $this->canonical_url = (string) ($page->canonical_url ?? '');
        $this->robots_meta = (string) ($page->robots_meta ?? '');
        $this->og_image = (string) ($page->og_image ?? '');
        $this->og_image_alt = (string) ($page->og_image_alt ?? '');
        $this->hreflang_json_input = $page->hreflang_json !== null
            ? json_encode($page->hreflang_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';
        $this->entity_tags_input = is_array($page->entity_tags) && $page->entity_tags !== []
            ? implode(', ', $page->entity_tags)
            : '';
        $this->fact_check_verified = (bool) $page->fact_check_verified;
        $this->content_reviewed_label = $page->content_reviewed_at !== null
            ? $page->content_reviewed_at->timezone(config('app.timezone'))->format('Y-m-d H:i')
            : '';
        $this->h1 = (string) ($page->h1 ?? '');
        $this->h2 = (string) ($page->h2 ?? '');
        $this->h3 = (string) ($page->h3 ?? '');
        $this->h4 = (string) ($page->h4 ?? '');
        $this->h5 = (string) ($page->h5 ?? '');
        $this->h6 = (string) ($page->h6 ?? '');
        $this->aeo_question = (string) ($page->aeo_question ?? '');
        $this->aeo_answer = (string) ($page->aeo_answer ?? '');
        $this->schema_json_input = $page->schema_json !== null
            ? json_encode($page->schema_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';
        $this->gtm_code = (string) ($page->gtm_code ?? '');
        $this->pixel_code = (string) ($page->pixel_code ?? '');
        $this->focusKeywords = $this->padStringList(
            is_array($page->focus_keywords) ? $page->focus_keywords : $this->keywordsFromLegacyField($page->keywords),
            10
        );
        $this->headingH2 = $this->padStringList(
            is_array($page->heading_h2) && $page->heading_h2 !== []
                ? $page->heading_h2
                : (filled($page->h2) ? [(string) $page->h2] : []),
            8
        );
        $this->headingH3 = $this->padStringList(
            is_array($page->heading_h3) && $page->heading_h3 !== []
                ? $page->heading_h3
                : (filled($page->h3) ? [(string) $page->h3] : []),
            8
        );
        $this->ai_context = (string) ($page->ai_context ?? '');
        $this->search_intent = (string) ($page->search_intent ?? '');
        $this->schema_type = (string) ($page->schema_type ?? '');
        $this->faqRows = $this->faqRowsFromPage($page);

        $this->contentParts = Page::parseContentTokens($page->content);

        $this->selectedPinIds = $page->pinCodes->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $this->pinPivot = [];
        foreach ($page->pinCodes as $pc) {
            $this->pinPivot[(int) $pc->id] = [
                'serviceability' => (bool) $pc->pivot->serviceability,
                'delivery_charge' => $pc->pivot->delivery_charge !== null ? (string) $pc->pivot->delivery_charge : '',
                'location_keywords' => (string) ($pc->pivot->location_keywords ?? ''),
            ];
        }
        foreach ($this->selectedPinIds as $pid) {
            $this->ensurePinPivotDefaults((int) $pid);
        }
    }

    public function cancelForm(): void
    {
        $this->mode = 'list';
        $this->editingId = null;
        $this->resetForm();
    }

    public function updatedTitle(): void
    {
        if ($this->editingId === null) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function applyHijackStrategy(string $strategyKey): void
    {
        if ($this->mode !== 'form') {
            return;
        }

        if ($this->editingId !== null) {
            $page = Page::query()->findOrFail($this->editingId);
            $this->authorize('update', $page);
        } else {
            $this->authorize('create', Page::class);
        }

        $strategies = app(HijackStrategyReader::class)->allStrategies();
        $strategy = $strategies[$strategyKey] ?? null;
        if (! is_array($strategy)) {
            $this->addError('hijack_strategy', __('That hijack strategy is no longer available.'));

            return;
        }

        $fields = app(HijackContentBridgeService::class)->extractPageSeoFields($strategy);

        if ($fields['meta_title'] !== null) {
            $this->meta_title = $fields['meta_title'];
        }

        if ($fields['meta_description'] !== null) {
            $this->meta_description = $fields['meta_description'];
        }

        if ($fields['h1'] !== null) {
            $this->h1 = $fields['h1'];
        }

        if ($fields['ai_context_note'] !== null) {
            $this->ai_context = trim($this->ai_context) !== ''
                ? trim($this->ai_context)."\n\n".$fields['ai_context_note']
                : $fields['ai_context_note'];
        }

        if ($fields['schema_type'] !== null && $this->schema_type === '') {
            $this->schema_type = $fields['schema_type'];
        }

        session()->flash('status', __('Hijack strategy applied — review SEO fields and save the page.'));
    }

    public function applyAndPublishHijackStrategy(string $strategyKey): void
    {
        if ($this->mode !== 'form' || $this->editingId === null) {
            $this->addError('hijack_strategy', __('Save the page first, then use one-click publish.'));

            return;
        }

        $page = Page::query()->findOrFail($this->editingId);
        $this->authorize('update', $page);

        try {
            $result = app(HijackContentBridgeService::class)->applyAndPublish($page, $strategyKey);
        } catch (\InvalidArgumentException $e) {
            $this->addError('hijack_strategy', $e->getMessage());

            return;
        }

        $page = $result['page'];
        $this->meta_title = (string) ($page->meta_title ?? '');
        $this->meta_description = (string) ($page->meta_description ?? '');
        $this->h1 = (string) ($page->h1 ?? '');
        $this->ai_context = (string) ($page->ai_context ?? '');
        $this->schema_type = (string) ($page->schema_type ?? '');
        if (is_array($page->focus_keywords)) {
            $this->focusKeywords = $this->padStringList($page->focus_keywords, 10);
        }

        session()->flash('status', __('One-click update complete — page and SEO entity synced for :path.', [
            'path' => $result['path'],
        ]));
    }

    public function markContentReviewed(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $page = Page::query()->findOrFail($this->editingId);
        $this->authorize('update', $page);
        $page->update(['content_reviewed_at' => now()]);
        $fresh = $page->fresh();
        $this->content_reviewed_label = $fresh?->content_reviewed_at !== null
            ? $fresh->content_reviewed_at->timezone(config('app.timezone'))->format('Y-m-d H:i')
            : '';
        session()->flash('status', __('Content review timestamp updated.'));
    }

    public function restoreRevision(int $revisionId): void
    {
        if ($this->editingId === null) {
            return;
        }

        $page = Page::query()->findOrFail($this->editingId);
        $this->authorize('update', $page);

        $revision = PageRevision::query()
            ->where('page_id', $this->editingId)
            ->findOrFail($revisionId);

        $snap = $revision->snapshot;
        if (! is_array($snap)) {
            return;
        }

        $this->title = (string) ($snap['title'] ?? '');
        $this->slug = (string) ($snap['slug'] ?? '');
        $this->is_active = (bool) ($snap['is_active'] ?? false);
        $this->layout_mode = (string) ($snap['layout_mode'] ?? PageLayoutMode::Contained->value);
        $this->meta_title = (string) ($snap['meta_title'] ?? '');
        $this->meta_description = (string) ($snap['meta_description'] ?? '');
        $this->keywords = (string) ($snap['keywords'] ?? '');
        $this->canonical_url = (string) ($snap['canonical_url'] ?? '');
        $this->robots_meta = (string) ($snap['robots_meta'] ?? '');
        $this->og_image = (string) ($snap['og_image'] ?? '');
        $this->og_image_alt = (string) ($snap['og_image_alt'] ?? '');
        $href = $snap['hreflang_json'] ?? null;
        $this->hreflang_json_input = is_array($href)
            ? json_encode($href, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';
        $tags = $snap['entity_tags'] ?? null;
        $this->entity_tags_input = is_array($tags)
            ? implode(', ', array_map('strval', $tags))
            : '';
        $this->fact_check_verified = (bool) ($snap['fact_check_verified'] ?? false);
        $this->content_reviewed_label = isset($snap['content_reviewed_at']) && is_string($snap['content_reviewed_at'])
            ? CarbonImmutable::parse($snap['content_reviewed_at'])->timezone(config('app.timezone'))->format('Y-m-d H:i')
            : '';
        $this->h1 = (string) ($snap['h1'] ?? '');
        $this->h2 = (string) ($snap['h2'] ?? '');
        $this->h3 = (string) ($snap['h3'] ?? '');
        $this->h4 = (string) ($snap['h4'] ?? '');
        $this->h5 = (string) ($snap['h5'] ?? '');
        $this->h6 = (string) ($snap['h6'] ?? '');
        $this->aeo_question = (string) ($snap['aeo_question'] ?? '');
        $this->aeo_answer = (string) ($snap['aeo_answer'] ?? '');
        $schema = $snap['schema_json'] ?? null;
        $this->schema_json_input = is_array($schema)
            ? json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';
        $this->gtm_code = (string) ($snap['gtm_code'] ?? '');
        $this->pixel_code = (string) ($snap['pixel_code'] ?? '');
        $this->focusKeywords = $this->padStringList(
            is_array($snap['focus_keywords'] ?? null) ? $snap['focus_keywords'] : $this->keywordsFromLegacyField((string) ($snap['keywords'] ?? '')),
            10
        );
        $this->headingH2 = $this->padStringList(is_array($snap['heading_h2'] ?? null) ? $snap['heading_h2'] : [], 8);
        $this->headingH3 = $this->padStringList(is_array($snap['heading_h3'] ?? null) ? $snap['heading_h3'] : [], 8);
        $this->ai_context = (string) ($snap['ai_context'] ?? '');
        $this->search_intent = (string) ($snap['search_intent'] ?? '');
        $this->schema_type = (string) ($snap['schema_type'] ?? '');
        $faqSnap = $snap['faqs'] ?? [];
        $this->faqRows = is_array($faqSnap) && $faqSnap !== []
            ? $this->padFaqRows(array_map(fn (array $row): array => [
                'question' => (string) ($row['question'] ?? ''),
                'answer' => (string) ($row['answer'] ?? ''),
            ], $faqSnap))
            : $this->padFaqRows([]);
        $this->contentParts = Page::parseContentTokens($snap['content'] ?? null);

        $this->selectedPinIds = [];
        $this->pinPivot = [];
        foreach ($snap['pin_codes'] ?? [] as $row) {
            if (! is_array($row) || empty($row['id'])) {
                continue;
            }
            $pid = (int) $row['id'];
            $this->selectedPinIds[] = $pid;
            $this->pinPivot[$pid] = [
                'serviceability' => (bool) ($row['serviceability'] ?? true),
                'delivery_charge' => isset($row['delivery_charge']) && $row['delivery_charge'] !== null ? (string) $row['delivery_charge'] : '',
                'location_keywords' => (string) ($row['location_keywords'] ?? ''),
            ];
        }
        $this->selectedPinIds = array_values(array_unique($this->selectedPinIds));

        session()->flash('status', __('Revision loaded into the form — review and click Save page.'));
    }

    public function confirmArchitectOverwriteSave(): void
    {
        $this->architectOverwriteApproved = true;
        $this->savePage();
    }

    public function confirmArchitectIncompleteSave(): void
    {
        $this->architectIncompleteSaveApproved = true;
        $this->savePage();
    }

    public function savePage(): void
    {
        if ($this->architectSaveBypassEligible() && $this->architectIncompleteSaveApproved) {
            if (trim($this->title) === '') {
                $this->title = __('Untitled page');
            }
            if (trim($this->slug) === '') {
                $this->slug = ArchitectSaveBypass::defaultBlockSlug($this->title, 'page-'.Str::lower(Str::random(8)));
            }
            if (trim((string) $this->layout_mode) === '') {
                $this->layout_mode = PageLayoutMode::Contained->value;
            }
        }

        $schemaDecoded = null;
        if (trim($this->schema_json_input) !== '') {
            $decoded = json_decode($this->schema_json_input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError('schema_json_input', __('Invalid JSON for schema.'));

                return;
            }
            $schemaDecoded = $decoded;
        }

        $hreflangDecoded = null;
        if (trim($this->hreflang_json_input) !== '') {
            $hrefDecoded = json_decode($this->hreflang_json_input, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($hrefDecoded)) {
                $this->addError('hreflang_json_input', __('Hreflang must be valid JSON object (locale → URL).'));

                return;
            }
            foreach ($hrefDecoded as $locale => $url) {
                if (! is_string($locale) || (! is_string($url) && ! is_scalar($url))) {
                    $this->addError('hreflang_json_input', __('Each hreflang entry must map a locale string to a URL string.'));

                    return;
                }
            }
            $hreflangDecoded = array_map(fn ($u) => is_string($u) ? $u : (string) $u, $hrefDecoded);
        }

        $entityTagsDecoded = null;
        if (trim($this->entity_tags_input) !== '') {
            $entityTagsDecoded = array_values(array_filter(array_map('trim', preg_split('/[,|\n]+/', $this->entity_tags_input) ?: [])));
            if ($entityTagsDecoded === []) {
                $entityTagsDecoded = null;
            }
        }

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
            'is_active' => ['boolean'],
            'layout_mode' => ['required', Rule::in(array_column(PageLayoutMode::cases(), 'value'))],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string'],
            'canonical_url' => ['nullable', 'string', 'max:2048'],
            'robots_meta' => ['nullable', 'string', 'max:255'],
            'og_image' => ['nullable', 'string', 'max:2048'],
            'og_image_alt' => ['nullable', 'string', 'max:255'],
            'fact_check_verified' => ['boolean'],
            'h1' => ['nullable', 'string', 'max:255'],
            'h2' => ['nullable', 'string', 'max:255'],
            'h3' => ['nullable', 'string', 'max:255'],
            'h4' => ['nullable', 'string', 'max:255'],
            'h5' => ['nullable', 'string', 'max:255'],
            'h6' => ['nullable', 'string', 'max:255'],
            'aeo_question' => ['nullable', 'string'],
            'aeo_answer' => ['nullable', 'string'],
            'ai_context' => ['nullable', 'string'],
            'search_intent' => ['nullable', 'string', 'max:255'],
            'schema_type' => ['nullable', 'string', 'max:120'],
            'gtm_code' => ['nullable', 'string'],
            'pixel_code' => ['nullable', 'string'],
            'focusKeywords' => ['nullable', 'array'],
            'focusKeywords.*' => ['string', 'max:120'],
            'headingH2' => ['nullable', 'array'],
            'headingH2.*' => ['string', 'max:500'],
            'headingH3' => ['nullable', 'array'],
            'headingH3.*' => ['string', 'max:500'],
            'faqRows' => ['nullable', 'array'],
            'faqRows.*.question' => ['nullable', 'string', 'max:2000'],
            'faqRows.*.answer' => ['nullable', 'string'],
        ];

        if (! $this->validateArchitectForm($rules, ['title', 'slug', 'layout_mode'])) {
            return;
        }

        if (! $this->assertArchitectUniqueAvailable(Page::class, 'slug', $this->slug, $this->editingId, 'title')) {
            return;
        }

        $focusKeywords = array_values(array_filter($this->focusKeywords, fn ($v) => is_string($v) && trim($v) !== ''));
        $headingH2 = array_values(array_filter($this->headingH2, fn ($v) => is_string($v) && trim($v) !== ''));
        $headingH3 = array_values(array_filter($this->headingH3, fn ($v) => is_string($v) && trim($v) !== ''));

        $content = Page::buildContentFromParts($this->contentParts);

        $previousSlug = null;
        if ($this->editingId !== null) {
            $previousSlug = Page::query()->whereKey($this->editingId)->value('slug');
        }

        $wasCreating = $this->editingId === null;
        $savedPageId = null;

        $data = [
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $content,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'keywords' => $focusKeywords !== [] ? implode(', ', $focusKeywords) : ($this->keywords ?: null),
            'focus_keywords' => $focusKeywords !== [] ? $focusKeywords : null,
            'canonical_url' => $this->canonical_url ?: null,
            'robots_meta' => $this->robots_meta !== '' ? $this->robots_meta : null,
            'og_image' => $this->og_image ?: null,
            'og_image_alt' => $this->og_image_alt ?: null,
            'hreflang_json' => $hreflangDecoded,
            'entity_tags' => $entityTagsDecoded,
            'fact_check_verified' => $this->fact_check_verified,
            'h1' => $this->h1 ?: null,
            'h2' => $this->h2 ?: ($headingH2[0] ?? null),
            'h3' => $this->h3 ?: ($headingH3[0] ?? null),
            'heading_h2' => $headingH2 !== [] ? $headingH2 : null,
            'heading_h3' => $headingH3 !== [] ? $headingH3 : null,
            'h4' => $this->h4 ?: null,
            'h5' => $this->h5 ?: null,
            'h6' => $this->h6 ?: null,
            'aeo_question' => $this->aeo_question ?: null,
            'aeo_answer' => $this->aeo_answer ?: null,
            'ai_context' => $this->ai_context ?: null,
            'search_intent' => $this->search_intent ?: null,
            'schema_json' => $schemaDecoded,
            'schema_type' => $this->schema_type ?: null,
            'gtm_code' => $this->gtm_code ?: null,
            'pixel_code' => $this->pixel_code ?: null,
            'is_active' => $this->is_active,
            'layout_mode' => $this->layout_mode,
        ];

        DB::transaction(function () use ($data, $previousSlug, &$savedPageId): void {
            if ($this->editingId === null) {
                $this->authorize('create', Page::class);
                $page = Page::query()->create($data);
                $this->editingId = $page->id;
            } else {
                $page = Page::query()->findOrFail($this->editingId);
                $this->authorize('update', $page);
                $page->update($data);
            }

            $page->refresh();
            $savedPageId = $page->id;

            if ($previousSlug !== null && $previousSlug !== $page->slug) {
                SiteSlugRedirect::query()->where('to_slug', $previousSlug)->update(['to_slug' => $page->slug]);
                SiteSlugRedirect::query()->updateOrCreate(
                    ['from_slug' => $previousSlug],
                    ['to_slug' => $page->slug]
                );
            }

            $sync = [];
            foreach ($this->selectedPinIds as $pid) {
                $pid = (int) $pid;
                $meta = $this->pinPivot[$pid] ?? [];
                $charge = $meta['delivery_charge'] ?? '';
                $sync[$pid] = [
                    'serviceability' => (bool) ($meta['serviceability'] ?? true),
                    'delivery_charge' => $charge !== '' && $charge !== null ? $charge : null,
                    'location_keywords' => ($meta['location_keywords'] ?? '') !== ''
                        ? $meta['location_keywords']
                        : null,
                ];
            }
            $page->pinCodes()->sync($sync);

            $this->syncPageFaqs($page);

            $page->refresh();
            $page->load(['pinCodes', 'faqs']);

            PageRevision::query()->create([
                'page_id' => $page->id,
                'user_id' => auth()->id(),
                'snapshot' => $page->toRevisionSnapshot(),
            ]);

            while (PageRevision::query()->where('page_id', $page->id)->count() > 40) {
                $oldest = PageRevision::query()
                    ->where('page_id', $page->id)
                    ->oldest('id')
                    ->first();
                if ($oldest === null) {
                    break;
                }
                $oldest->delete();
            }
        });

        if ($savedPageId !== null) {
            $savedPage = Page::query()->find($savedPageId);
            if ($savedPage !== null) {
                \App\Support\ServicePageOverrides::markAdminSave($savedPage);
            }

            app(ActivityLogService::class)->log(
                $wasCreating ? 'page_create' : 'page_update',
                'site_architect',
                'Page ID '.$savedPageId
            );

            if ($data['is_active'] ?? false) {
                app(OutboundWebhookDispatcher::class)->dispatch('page.published', [
                    'page_id' => $savedPageId,
                    'slug' => $data['slug'],
                    'title' => $data['title'],
                ]);
            }
        }

        session()->flash('status', __('Page saved.'));
        $this->resetArchitectSaveFlags();

        if ($wasCreating) {
            $this->mode = 'list';
            $this->editingId = null;
            $this->resetForm();
            $this->resetPage();
        } elseif ($savedPageId !== null) {
            $this->previewRefreshNonce++;
            $this->startEdit($savedPageId);
        } else {
            $this->mode = 'list';
            $this->editingId = null;
            $this->resetForm();
            $this->resetPage();
        }
    }

    public function deletePage(int $id): void
    {
        $page = Page::query()->findOrFail($id);
        $this->authorize('delete', $page);
        app(\App\Services\Governance\AdminAuthorityGuard::class)->markDeletedByAdmin($page);
        app(\App\Services\Governance\DownstreamArtifactPurger::class)->purgeForDeletedPage($page);
        $page->delete();
        app(ActivityLogService::class)->log(
            'page_delete',
            'site_architect',
            'Page ID '.$id
        );
        session()->flash('status', __('Page deleted.'));
        $this->resetPage();
    }

    public function duplicatePage(int $id): void
    {
        $original = Page::query()->with('pinCodes')->findOrFail($id);
        $this->authorize('create', Page::class);

        $newPageId = null;
        DB::transaction(function () use ($original, &$newPageId): void {
            $new = $original->replicate();
            $new->uuid = (string) Str::uuid();
            $new->title = $original->title.' ('.__('Copy').')';
            $baseSlug = $original->slug.'-copy';
            $new->slug = $baseSlug;
            $n = 1;
            while (Page::query()->where('slug', $new->slug)->exists()) {
                $new->slug = $baseSlug.'-'.$n;
                $n++;
            }
            $new->is_active = false;
            $new->save();
            $newPageId = $new->id;

            $sync = [];
            foreach ($original->pinCodes as $pc) {
                $sync[$pc->id] = [
                    'serviceability' => (bool) $pc->pivot->serviceability,
                    'delivery_charge' => $pc->pivot->delivery_charge,
                    'location_keywords' => $pc->pivot->location_keywords,
                ];
            }
            $new->pinCodes()->sync($sync);
        });

        if ($newPageId !== null) {
            app(ActivityLogService::class)->log(
                'page_duplicate',
                'site_architect',
                'New page ID '.$newPageId.' from source '.$id
            );
        }

        session()->flash('status', __('Page duplicated.'));
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $page = Page::query()->findOrFail($id);
        $this->authorize('update', $page);
        $page->update(['is_active' => ! $page->is_active]);
        $page->refresh();

        if ($page->is_active) {
            app(OutboundWebhookDispatcher::class)->dispatch('page.published', [
                'page_id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
            ]);
        }
    }

    /**
     * Opens the block modal for a new block (no Livewire `null` argument — wire passes strings badly).
     */
    public function addSection(): void
    {
        $this->openSectionPicker();
    }

    public function addBlock(): void
    {
        $this->addSection();
    }

    public function openDeveloperBlockModal(): void
    {
        $this->closeSectionPicker();
        $this->openBlockModal(null);
    }

    public function openBlockModal(mixed $slug = null): void
    {
        if ($slug === null || $slug === '' || (is_string($slug) && strtolower($slug) === 'null')) {
            $slug = null;
        } elseif (! is_string($slug)) {
            $slug = (string) $slug;
        }

        $this->blockEditingSlug = $slug;
        if ($slug !== null) {
            $block = Block::query()->where('block_slug', $slug)->firstOrFail();
            $this->block_name = $block->block_name;
            $this->block_slug = $block->block_slug;
            $this->block_code = (string) ($block->code ?? '');
            $this->block_custom_css = (string) ($block->custom_css ?? '');
        } else {
            $this->block_name = '';
            $this->block_slug = '';
            $this->block_code = '';
            $this->block_custom_css = '';
        }
        $this->blockModalOpen = true;
        $this->serviceCatalogNonce++;
    }

    public function closeBlockModal(): void
    {
        $this->blockModalOpen = false;
        $this->blockEditingSlug = null;
    }

    public function updatedBlockName(string $value): void
    {
        if ($this->blockEditingSlug === null && $this->block_slug === '') {
            $this->block_slug = Str::slug($value);
        }
    }

    public function saveBlockInModal(): void
    {
        if ($this->blockEditingSlug !== null) {
            $existing = Block::query()->where('block_slug', $this->blockEditingSlug)->first();
            if ($existing?->is_managed) {
                $this->addError('block_code', __('Managed blocks cannot be edited here. Use Blocks Studio for content/media or run blocks:sync for Git templates.'));

                return;
            }
        }

        $blockId = Block::query()->where('block_slug', $this->block_slug)->value('id');

        if ($this->architectSaveBypassEligible() && $this->architectIncompleteSaveApproved) {
            if (trim($this->block_name) === '') {
                $this->block_name = ArchitectSaveBypass::defaultBlockName();
            }
            if (trim($this->block_slug) === '') {
                $this->block_slug = ArchitectSaveBypass::defaultBlockSlug($this->block_name);
            }
            if (trim($this->block_code) === '') {
                $this->block_code = '<!-- -->';
            }
        }

        if (! $this->validateArchitectForm([
            'block_name' => ['required', 'string', 'max:255'],
            'block_slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
            'block_code' => ['required', 'string'],
            'block_custom_css' => ['nullable', 'string'],
        ], ['block_name', 'block_slug', 'block_code'])) {
            return;
        }

        if (! $this->assertArchitectUniqueAvailable(Block::class, 'block_slug', $this->block_slug, $blockId ? (int) $blockId : null)) {
            return;
        }

        if ($this->blockEditingSlug !== null && $this->blockEditingSlug !== $this->block_slug) {
            foreach ($this->contentParts as $i => $part) {
                if ($part['type'] === 'block' && $part['slug'] === $this->blockEditingSlug) {
                    $this->contentParts[$i]['slug'] = $this->block_slug;
                }
            }
        }

        $customCss = trim($this->block_custom_css);

        Block::query()->updateOrCreate(
            ['block_slug' => $this->block_slug],
            [
                'block_name' => $this->block_name,
                'code' => $this->block_code,
                'custom_css' => $customCss !== '' ? $customCss : null,
            ]
        );

        if ($this->blockEditingSlug === null) {
            $this->contentParts[] = ['type' => 'block', 'slug' => $this->block_slug];
        }

        $this->closeBlockModal();
    }

    public function appendModule(): void
    {
        $key = trim($this->module_choice);
        $catalog = app(DynamicModuleInsertCatalog::class);

        if ($key === '' || ! $catalog->isValidKey($key)) {
            $this->addError('module_choice', __('Choose a module.'));

            return;
        }
        $this->contentParts[] = ['type' => 'module', 'slug' => $key];
        $this->module_choice = '';
    }

    public function appendModuleTokenToBlock(): void
    {
        $key = trim($this->block_module_choice);
        $catalog = app(DynamicModuleInsertCatalog::class);

        if ($key === '' || ! $catalog->isValidKey($key)) {
            $this->addError('block_module_choice', __('Choose a module.'));

            return;
        }

        $token = '{{module:'.$key.'}}';
        $this->block_code = str_contains($this->block_code, $token)
            ? $this->block_code
            : ($this->block_code === '' ? $token : rtrim($this->block_code)."\n".$token);
        $this->block_module_choice = '';
    }

    /**
     * Insert {{service:CODE}} into the open block modal's code textarea.
     * Service tokens live INSIDE block code (not at page level), and the
     * block's Blade markup decides how to render the loaded service.
     */
    public function refreshServiceInsertCatalog(): void
    {
        $this->serviceCatalogNonce++;
    }

    public function appendServiceToken(): void
    {
        $code = trim($this->service_choice);
        if ($code === '') {
            $this->addError('service_choice', __('Choose a service to insert.'));

            return;
        }

        $catalog = app(ServiceInsertCatalog::class);

        if (! $catalog->existsForToken($code)) {
            $this->addError('service_choice', __('That service code was not found. Refresh the list or create the service first.'));

            return;
        }

        $token = '{{service:'.$code.'}}';
        $this->block_code = str_contains($this->block_code, $token)
            ? $this->block_code
            : ($this->block_code === '' ? $token : rtrim($this->block_code)."\n".$token);
        $this->service_choice = '';
    }

    public function removePart(int $index): void
    {
        unset($this->contentParts[$index]);
        $this->contentParts = array_values($this->contentParts);

        if ($this->editingId !== null) {
            app(ActivityLogService::class)->log(
                'block_remove_from_page',
                'site_architect',
                'Page '.$this->editingId.' removed structure row index '.$index
            );
        }
    }

    public function movePartUp(int $index): void
    {
        if ($index <= 0) {
            return;
        }
        $tmp = $this->contentParts[$index - 1];
        $this->contentParts[$index - 1] = $this->contentParts[$index];
        $this->contentParts[$index] = $tmp;
    }

    public function movePartDown(int $index): void
    {
        if ($index >= count($this->contentParts) - 1) {
            return;
        }
        $tmp = $this->contentParts[$index + 1];
        $this->contentParts[$index + 1] = $this->contentParts[$index];
        $this->contentParts[$index] = $tmp;
    }

    public function editBlockFromPart(int $index): void
    {
        $part = $this->contentParts[$index] ?? null;
        if (($part['type'] ?? '') !== 'block') {
            return;
        }

        $slug = (string) ($part['slug'] ?? '');
        if ($slug === '') {
            return;
        }

        $block = Block::query()->where('block_slug', $slug)->first();
        if ($block !== null && ($block->is_managed || BlockContent::hasSchema($slug))) {
            $this->redirect(route('site-architect.block-studio.index', ['block' => $slug]));

            return;
        }

        $this->openBlockModal($slug);
    }

    public function updatedSelectedPinIds(): void
    {
        foreach ($this->selectedPinIds as $pid) {
            $this->ensurePinPivotDefaults((int) $pid);
        }
        $this->selectedPinIds = array_values(array_unique(array_map('intval', $this->selectedPinIds)));
    }

    protected function ensurePinPivotDefaults(int $pinId): void
    {
        if (! isset($this->pinPivot[$pinId])) {
            $this->pinPivot[$pinId] = [
                'serviceability' => true,
                'delivery_charge' => '',
                'location_keywords' => '',
            ];
        }
    }

    /**
     * @return list<string>
     */
    protected function pageFocusKeywords(): array
    {
        $fromFocus = array_values(array_filter(
            $this->focusKeywords,
            fn ($kw) => is_string($kw) && trim($kw) !== ''
        ));

        if ($fromFocus !== []) {
            return $fromFocus;
        }

        return $this->keywordsFromLegacyField($this->keywords);
    }

    /**
     * @return array{score: int, avg_words_per_sentence: float|null, note: string}
     */
    protected function computeReadabilityHint(): array
    {
        $text = collect([
            $this->title,
            $this->meta_description,
            $this->h1,
            $this->aeo_answer,
            $this->keywords,
        ])->filter()->implode(' ');

        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        if ($plain === '') {
            return ['score' => 0, 'avg_words_per_sentence' => null, 'note' => __('Add title, meta description, or H1 to score readability.')];
        }

        $words = str_word_count($plain);
        $sentenceCount = max(1, preg_match_all('/[.!?]+/', $plain) ?: 1);
        $avg = $words / $sentenceCount;
        $score = (int) max(0, min(100, 100 - min(55, (int) (abs($avg - 17) * 2.2))));

        return [
            'score' => $score,
            'avg_words_per_sentence' => round($avg, 1),
            'note' => __('Shorter sentences (roughly 12–20 words) usually read better on the web.'),
        ];
    }

    /**
     * @return array{score: int, checks: list<string>}
     */
    protected function computeLlmReadiness(): array
    {
        $checks = [];
        $score = 0;
        if ($this->meta_title !== '') {
            $score += 12;
            $checks[] = __('Meta title present');
        }
        if ($this->meta_description !== '') {
            $score += 12;
            $checks[] = __('Meta description present');
        }
        if ($this->keywords !== '') {
            $score += 8;
            $checks[] = __('Focus keywords captured');
        }
        if ($this->aeo_question !== '' && $this->aeo_answer !== '') {
            $score += 18;
            $checks[] = __('AEO question & answer pair complete');
        }
        if (trim($this->schema_json_input) !== '') {
            $score += 15;
            $checks[] = __('Structured data JSON present');
        }
        if (trim(preg_replace('/[\s,|]+/u', '', $this->entity_tags_input) ?? '') !== '') {
            $score += 10;
            $checks[] = __('Entity tags defined');
        }
        if ($this->fact_check_verified) {
            $score += 10;
            $checks[] = __('Fact-check marked as verified');
        }
        if ($this->h1 !== '') {
            $score += 8;
            $checks[] = __('H1 present');
        }
        if ($this->contentParts !== []) {
            $score += 7;
            $checks[] = __('Page structure has blocks or modules');
        }

        return ['score' => min(100, $score), 'checks' => $checks];
    }

    /**
     * Rank Math–style on-page checklist (lightweight signals).
     *
     * @return array{score: int, checks: list<string>, warnings: list<string>}
     */
    protected function computeOnPageSeoChecklist(): array
    {
        $checks = [];
        $warnings = [];
        $score = 0;

        $mtLen = strlen(trim($this->meta_title));
        if ($mtLen >= 15 && $mtLen <= 70) {
            $score += 14;
            $checks[] = __('Meta title length looks suitable for SERPs.');
        } elseif ($this->meta_title !== '') {
            $warnings[] = __('Tune meta title length (roughly 30–60 characters).');
        }

        $mdLen = strlen(trim($this->meta_description));
        if ($mdLen >= 70 && $mdLen <= 320) {
            $score += 14;
            $checks[] = __('Meta description length is in a typical range.');
        } elseif ($this->meta_description !== '') {
            $warnings[] = __('Meta description may be too short or long for snippets.');
        }

        if ($this->canonical_url !== '') {
            $score += 8;
            $checks[] = __('Canonical URL set.');
        }

        if ($this->og_image !== '') {
            $score += 8;
            $checks[] = __('Open Graph image URL present.');
            if ($this->og_image_alt === '') {
                $warnings[] = __('Add OG image alt text for accessibility / social previews.');
            } else {
                $score += 6;
                $checks[] = __('OG image alt text present.');
            }
        }

        $primaryKw = '';
        foreach ($this->focusKeywords as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $primaryKw = strtolower(trim($candidate));
                break;
            }
        }
        if ($primaryKw === '') {
            $kw = strtolower(trim($this->keywords));
            $primaryKw = $kw !== '' ? strtolower(trim(explode(',', $kw)[0])) : '';
        }
        $h1 = strtolower(trim($this->h1));
        if ($primaryKw !== '' && $h1 !== '' && str_contains($h1, $primaryKw)) {
            $score += 12;
            $checks[] = __('Primary keyword appears reflected in H1.');
        }

        if ($this->robots_meta !== '') {
            $score += 6;
            $checks[] = __('Robots meta expressed.');
        }

        return [
            'score' => min(100, $score),
            'checks' => $checks,
            'warnings' => $warnings,
        ];
    }

    public static function defaultKeywordHints(PinCode $pc): array
    {
        return [
            __('Home Care in :pin', ['pin' => $pc->pincode]),
            __('Nursing service in :area', ['area' => $pc->area_name]),
        ];
    }

    /**
     * @param  list<string>  $items
     * @return list<string>
     */
    protected function padStringList(array $items, int $size): array
    {
        $items = array_values(array_map(fn ($v) => is_string($v) ? $v : '', $items));

        return array_pad($items, $size, '');
    }

    /**
     * @return list<string>
     */
    protected function keywordsFromLegacyField(?string $keywords): array
    {
        if ($keywords === null || trim($keywords) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,|\n]+/', $keywords) ?: [])));
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    protected function faqRowsFromPage(Page $page): array
    {
        $rows = $page->faqs->map(fn (PageFaq $faq) => [
            'question' => $faq->question,
            'answer' => $faq->answer,
        ])->values()->all();

        return $this->padFaqRows($rows);
    }

    /**
     * @param  list<array{question: string, answer: string}>  $rows
     * @return list<array{question: string, answer: string}>
     */
    protected function padFaqRows(array $rows): array
    {
        if (count($rows) < 5) {
            $rows = array_pad($rows, 5, ['question' => '', 'answer' => '']);
        }

        return $rows;
    }

    protected function syncPageFaqs(Page $page): void
    {
        $page->faqs()->delete();
        $order = 0;
        foreach ($this->faqRows as $row) {
            $question = trim((string) ($row['question'] ?? ''));
            $answer = trim((string) ($row['answer'] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }
            PageFaq::query()->create([
                'page_id' => $page->id,
                'sort_order' => $order,
                'question' => $question,
                'answer' => $answer,
            ]);
            $order++;
        }
    }

    protected function resetForm(): void
    {
        $this->title = '';
        $this->slug = '';
        $this->page_source = '';
        $this->is_active = false;
        $this->layout_mode = PageLayoutMode::Contained->value;
        $this->meta_title = '';
        $this->meta_description = '';
        $this->keywords = '';
        $this->canonical_url = '';
        $this->robots_meta = '';
        $this->og_image = '';
        $this->og_image_alt = '';
        $this->hreflang_json_input = '';
        $this->entity_tags_input = '';
        $this->fact_check_verified = false;
        $this->content_reviewed_label = '';
        $this->h1 = '';
        $this->h2 = '';
        $this->h3 = '';
        $this->h4 = '';
        $this->h5 = '';
        $this->h6 = '';
        $this->aeo_question = '';
        $this->aeo_answer = '';
        $this->schema_json_input = '';
        $this->schema_type = '';
        $this->gtm_code = '';
        $this->pixel_code = '';
        $this->focusKeywords = $this->padStringList([], 10);
        $this->headingH2 = $this->padStringList([], 8);
        $this->headingH3 = $this->padStringList([], 8);
        $this->ai_context = '';
        $this->search_intent = '';
        $this->faqRows = $this->padFaqRows([]);
        $this->contentParts = [];
        $this->selectedPinIds = [];
        $this->pinPivot = [];
        $this->module_choice = '';
        $this->service_choice = '';
        $this->resetValidation();
    }
}
