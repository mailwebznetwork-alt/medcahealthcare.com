<?php

namespace App\Livewire\SiteArchitect;

use App\Models\Block;
use App\Models\Page;
use App\Models\PinCode;
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

        return view('livewire.site-architect.pages', [
            'pages' => $pages,
            'pinCodes' => $pinCodes,
            'modules' => $modules,
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

        $data = [
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $content,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'keywords' => $this->keywords ?: null,
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

        DB::transaction(function () use ($data): void {
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
        });

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
        session()->flash('status', __('Page deleted.'));
        $this->resetPage();
    }

    public function duplicatePage(int $id): void
    {
        $original = Page::query()->with('pinCodes')->findOrFail($id);
        $this->authorize('create', Page::class);

        DB::transaction(function () use ($original): void {
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
