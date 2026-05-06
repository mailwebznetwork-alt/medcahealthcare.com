<?php

namespace App\Livewire\SiteArchitect;

use App\Models\Block;
use App\Models\Page;
use App\Models\PageRevision;
use App\Models\PinCode;
use App\Models\SiteSlugRedirect;
use App\Services\ActivityLogService;
use App\Services\Growth\AiPulseService;
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
    use WithPagination;

    public function mount(): void
    {
        if (request()->query('create') === '1') {
            $this->startCreate();
        }
    }

    public string $mode = 'list';

    public ?int $editingId = null;

    public string $title = '';

    public string $slug = '';

    public bool $is_active = false;

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

    /** @var list<array{type: string, slug: string}> */
    public array $contentParts = [];

    /** @var list<int> */
    public array $selectedPinIds = [];

    /** @var array<int, array{serviceability: bool, delivery_charge: ?string, location_keywords: string}> */
    public array $pinPivot = [];

    public bool $blockModalOpen = false;

    public ?string $blockEditingSlug = null;

    public string $block_name = '';

    public string $block_slug = '';

    public string $block_code = '';

    public string $module_choice = '';

    public function render()
    {
        $pages = Page::query()->latest()->paginate(12);

        $pinCodes = PinCode::query()
            ->where('is_active', true)
            ->orderBy('city')
            ->orderBy('pincode')
            ->get(['id', 'pincode', 'area_name', 'city']);

        $modules = collect(config('modules', []))->keys()->values()->all();

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

        return view('livewire.site-architect.pages', [
            'pages' => $pages,
            'pinCodes' => $pinCodes,
            'modules' => $modules,
            'otherPagesForLinks' => $otherPagesForLinks,
            'revisions' => $revisions,
            'readabilityHint' => $this->mode === 'form' ? $this->computeReadabilityHint() : null,
            'llmReadiness' => $this->mode === 'form' ? $this->computeLlmReadiness() : null,
        ]);
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
        $page = Page::query()->with('pinCodes')->findOrFail($id);
        $this->authorize('update', $page);

        $this->resetForm();
        $this->editingId = $id;
        $this->mode = 'form';

        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->is_active = $page->is_active;
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

    public function savePage(): void
    {
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
                Rule::unique('pages', 'slug')->ignore($this->editingId),
            ],
            'is_active' => ['boolean'],
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
            'gtm_code' => ['nullable', 'string'],
            'pixel_code' => ['nullable', 'string'],
        ];

        $this->validate($rules);

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
            'keywords' => $this->keywords ?: null,
            'canonical_url' => $this->canonical_url ?: null,
            'robots_meta' => $this->robots_meta !== '' ? $this->robots_meta : null,
            'og_image' => $this->og_image ?: null,
            'og_image_alt' => $this->og_image_alt ?: null,
            'hreflang_json' => $hreflangDecoded,
            'entity_tags' => $entityTagsDecoded,
            'fact_check_verified' => $this->fact_check_verified,
            'h1' => $this->h1 ?: null,
            'h2' => $this->h2 ?: null,
            'h3' => $this->h3 ?: null,
            'h4' => $this->h4 ?: null,
            'h5' => $this->h5 ?: null,
            'h6' => $this->h6 ?: null,
            'aeo_question' => $this->aeo_question ?: null,
            'aeo_answer' => $this->aeo_answer ?: null,
            'schema_json' => $schemaDecoded,
            'gtm_code' => $this->gtm_code ?: null,
            'pixel_code' => $this->pixel_code ?: null,
            'is_active' => $this->is_active,
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

            $page->refresh();
            $page->load('pinCodes');

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
            app(ActivityLogService::class)->log(
                $wasCreating ? 'page_create' : 'page_update',
                'site_architect',
                'Page ID '.$savedPageId
            );
            app(AiPulseService::class)->triggerAuditAfterPublish();
        }

        session()->flash('status', __('Page saved.'));
        $this->mode = 'list';
        $this->editingId = null;
        $this->resetForm();
        $this->resetPage();
    }

    public function deletePage(int $id): void
    {
        $page = Page::query()->findOrFail($id);
        $this->authorize('delete', $page);
        $page->delete();
        app(ActivityLogService::class)->log(
            'page_delete',
            'site_architect',
            'Page ID '.$id
        );
        app(AiPulseService::class)->triggerAuditAfterPublish();
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
            app(AiPulseService::class)->triggerAuditAfterPublish();
        }

        session()->flash('status', __('Page duplicated.'));
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $page = Page::query()->findOrFail($id);
        $this->authorize('update', $page);
        $page->update(['is_active' => ! $page->is_active]);
    }

    /**
     * Opens the block modal for a new block (no Livewire `null` argument — wire passes strings badly).
     */
    public function addBlock(): void
    {
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
            $this->block_code = $block->code;
        } else {
            $this->block_name = '';
            $this->block_slug = '';
            $this->block_code = '';
        }
        $this->blockModalOpen = true;
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
        $blockId = Block::query()->where('block_slug', $this->block_slug)->value('id');

        $this->validate([
            'block_name' => ['required', 'string', 'max:255'],
            'block_slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('blocks', 'block_slug')->ignore($blockId),
            ],
            'block_code' => ['required', 'string'],
        ]);

        if ($this->blockEditingSlug !== null && $this->blockEditingSlug !== $this->block_slug) {
            foreach ($this->contentParts as $i => $part) {
                if ($part['type'] === 'block' && $part['slug'] === $this->blockEditingSlug) {
                    $this->contentParts[$i]['slug'] = $this->block_slug;
                }
            }
        }

        Block::query()->updateOrCreate(
            ['block_slug' => $this->block_slug],
            ['block_name' => $this->block_name, 'code' => $this->block_code]
        );

        if ($this->blockEditingSlug === null) {
            $this->contentParts[] = ['type' => 'block', 'slug' => $this->block_slug];
        }

        $this->closeBlockModal();
    }

    public function appendModule(): void
    {
        $key = trim($this->module_choice);
        if ($key === '' || ! array_key_exists($key, config('modules', []))) {
            $this->addError('module_choice', __('Choose a module.'));

            return;
        }
        $this->contentParts[] = ['type' => 'module', 'slug' => $key];
        $this->module_choice = '';
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
        $this->openBlockModal($part['slug']);
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

    public static function defaultKeywordHints(PinCode $pc): array
    {
        return [
            __('Home Care in :pin', ['pin' => $pc->pincode]),
            __('Nursing service in :area', ['area' => $pc->area_name]),
        ];
    }

    protected function resetForm(): void
    {
        $this->title = '';
        $this->slug = '';
        $this->is_active = false;
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
        $this->gtm_code = '';
        $this->pixel_code = '';
        $this->contentParts = [];
        $this->selectedPinIds = [];
        $this->pinPivot = [];
        $this->module_choice = '';
        $this->resetValidation();
    }
}
