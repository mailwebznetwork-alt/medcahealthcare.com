<?php

namespace App\Livewire\SiteArchitect;

use App\Livewire\Concerns\HandlesArchitectFlexibleSave;
use App\Models\Block;
use App\Services\DynamicModules\DynamicModuleInsertCatalog;
use App\Services\ContentParser;
use App\Services\SiteArchitect\ServiceInsertCatalog;
use App\Support\ArchitectSaveBypass;
use App\Services\Blocks\BlockContextExporter;
use App\Support\BlockContent;
use App\Services\Deployment\BlockSettingsEditor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class BlockFactory extends Component
{
    use AuthorizesRequests;
    use HandlesArchitectFlexibleSave;
    use WithPagination;

    public string $mode = 'list';

    public ?int $editingId = null;

    public bool $editingManaged = false;

    public string $block_name = '';

    public string $block_slug = '';

    public string $description = '';

    public string $block_type = '';

    public string $code = '';

    public string $custom_css = '';

    public string $schema_json_input = '';

    public bool $is_active = true;

    public bool $previewOpen = false;

    public string $previewHtml = '';

    public string $previewError = '';

    public ?int $previewBlockId = null;

    public string $service_choice = '';

    public string $module_choice = '';

    public int $serviceCatalogNonce = 0;

    public string $search = '';

    /** @var array<string, string> */
    public array $block_content = [];

    public function mount(): void
    {
        if (request()->query('create') === '1') {
            $this->startCreate();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Block::query()->latest();

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('block_name', 'like', $term)
                    ->orWhere('block_slug', 'like', $term)
                    ->orWhere('block_type', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        $blocks = $query->paginate(15);

        $typesByGroup = config('block_factory.types_by_group', []);
        $allowedTypes = collect($typesByGroup)->flatten()->unique()->values()->all();

        $services = $this->mode === 'form'
            ? app(ServiceInsertCatalog::class)->forDropdown()
            : collect();

        $moduleOptions = $this->mode === 'form'
            ? app(DynamicModuleInsertCatalog::class)->forDropdown()
            : [];

        return view('livewire.site-architect.block-factory', [
            'blocks' => $blocks,
            'typesByGroup' => $typesByGroup,
            'allowedTypes' => $allowedTypes,
            'services' => $services,
            'moduleOptions' => $moduleOptions,
            'serviceCatalogNonce' => $this->serviceCatalogNonce,
            'editingManaged' => $this->editingManaged,
            'contentSchema' => $this->mode === 'form'
                ? BlockContent::schema(BlockContent::resolveSchemaSlug($this->block_slug, $this->code))
                : [],
            'showMarketingCopy' => $this->mode === 'form'
                && BlockContent::marketingCopyVisible($this->block_slug, $this->code),
        ]);
    }

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
        $this->code = str_contains($this->code, $token)
            ? $this->code
            : ($this->code === '' ? $token : rtrim($this->code)."\n".$token);
        $this->service_choice = '';
    }

    public function appendModuleToken(): void
    {
        $key = trim($this->module_choice);
        $catalog = app(DynamicModuleInsertCatalog::class);

        if ($key === '' || ! $catalog->isValidKey($key)) {
            $this->addError('module_choice', __('Choose a module.'));

            return;
        }

        $token = '{{module:'.$key.'}}';
        $this->code = str_contains($this->code, $token)
            ? $this->code
            : ($this->code === '' ? $token : rtrim($this->code)."\n".$token);
        $this->module_choice = '';
    }

    public function startCreate(): void
    {
        $this->authorize('create', Block::class);

        $this->resetForm();
        $this->mode = 'form';
        $this->editingId = null;
        $this->serviceCatalogNonce++;
    }

    public function startEdit(int $id): void
    {
        $block = Block::query()->findOrFail($id);
        $this->authorize('update', $block);

        $this->resetForm();
        $this->editingId = $id;
        $this->mode = 'form';

        $this->block_name = $block->block_name;
        $this->block_slug = $block->block_slug;
        $this->description = (string) ($block->description ?? '');
        $this->block_type = (string) ($block->block_type ?? '');
        $this->code = (string) ($block->code ?? '');
        $this->custom_css = (string) ($block->custom_css ?? '');
        $this->serviceCatalogNonce++;
        $this->schema_json_input = $block->schema_json !== null
            ? json_encode($block->schema_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';
        $this->is_active = $block->is_active;
        $this->editingManaged = (bool) $block->is_managed;
        $this->loadBlockMarketingContent($block);
    }

    public function updatedCode(): void
    {
        $block = $this->editingId !== null ? Block::query()->find($this->editingId) : null;
        $this->loadBlockMarketingContent($block instanceof Block ? $block : null);
    }

    public function updatedBlockSlug(): void
    {
        if ($this->editingId === null) {
            $this->loadBlockMarketingContent(null);
        }
    }

    public function cancelForm(): void
    {
        $this->mode = 'list';
        $this->editingId = null;
        $this->editingManaged = false;
        $this->resetForm();
        $this->resetArchitectSaveFlags();
    }

    public function confirmArchitectOverwriteSave(): void
    {
        $this->architectOverwriteApproved = true;
        $this->saveBlock();
    }

    public function confirmArchitectIncompleteSave(): void
    {
        $this->architectIncompleteSaveApproved = true;
        $this->saveBlock();
    }

    public function updatedBlockName(string $value): void
    {
        if ($this->editingId === null && $this->block_slug === '') {
            $this->block_slug = Str::slug($value);
        }
    }

    public function saveBlock(): void
    {
        if ($this->architectSaveBypassEligible() && $this->architectIncompleteSaveApproved) {
            if (trim($this->block_name) === '') {
                $this->block_name = ArchitectSaveBypass::defaultBlockName();
            }
            if (trim($this->block_slug) === '') {
                $this->block_slug = ArchitectSaveBypass::defaultBlockSlug($this->block_name);
            }
            if (trim($this->code) === '') {
                $this->code = '<!-- -->';
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

        if (! $this->validateArchitectForm([
            'block_name' => ['required', 'string', 'max:255'],
            'block_slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
            'description' => ['nullable', 'string'],
            'block_type' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string'],
            'custom_css' => ['nullable', 'string'],
            'schema_json_input' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ], ['block_name', 'block_slug', 'code'])) {
            return;
        }

        if (! $this->assertArchitectUniqueAvailable(Block::class, 'block_slug', $this->block_slug, $this->editingId)) {
            return;
        }

        $data = [
            'block_name' => $this->block_name,
            'block_slug' => $this->block_slug,
            'description' => $this->description !== '' ? $this->description : null,
            'block_type' => $this->block_type !== '' ? $this->block_type : null,
            'code' => $this->code,
            'custom_css' => trim($this->custom_css) !== '' ? trim($this->custom_css) : null,
            'schema_json' => $schemaDecoded,
            'is_active' => $this->is_active,
        ];

        DB::transaction(function () use ($data): void {
            $block = null;
            if ($this->editingId === null) {
                $this->authorize('create', Block::class);
                $block = Block::query()->create($data);
            } else {
                $block = Block::query()->findOrFail($this->editingId);
                $this->authorize('update', $block);
                if ($block->is_managed) {
                    $block->update([
                        'block_name' => $data['block_name'],
                        'block_slug' => $data['block_slug'],
                        'description' => $data['description'],
                        'block_type' => $data['block_type'],
                        'custom_css' => $data['custom_css'],
                        'is_active' => $data['is_active'],
                    ]);
                } else {
                    $block->update($data);
                }
            }

            if ($block !== null && BlockContent::marketingCopyVisible((string) $block->block_slug, (string) $block->code)) {
                $schemaSlug = BlockContent::resolveSchemaSlug((string) $block->block_slug, (string) $block->code);
                $allowed = array_keys(BlockContent::schema($schemaSlug));
                $content = array_intersect_key(
                    $this->block_content,
                    array_flip($allowed)
                );
                app(BlockSettingsEditor::class)->save($block, ['content' => $content]);
            }
        });

        session()->flash('status', __('Block saved.'));
        $this->mode = 'list';
        $this->editingId = null;
        $this->resetForm();
        $this->resetArchitectSaveFlags();
        $this->resetPage();
    }

    public function removeBlock(int $id): void
    {
        $block = Block::query()->findOrFail($id);

        if ($block->is_managed && ! $this->architectSaveBypassEligible()) {
            session()->flash('error', __('Managed blocks cannot be removed here. Duplicate to create an editable copy, or use a WDJERRIE account to remove from the database.'));

            return;
        }

        $this->authorize('delete', $block);

        $wasManaged = $block->is_managed;
        app(\App\Services\Governance\AdminAuthorityGuard::class)->markDeletedByAdmin($block);
        $block->delete();

        if ($this->editingId === $id) {
            $this->cancelForm();
        }

        session()->flash(
            'status',
            $wasManaged
                ? __('Managed block removed. Auto-heal will not restore it without explicit admin action.')
                : __('Block removed.')
        );
        $this->resetPage();
    }

    /** @deprecated Use removeBlock() */
    public function deleteBlock(int $id): void
    {
        $this->removeBlock($id);
    }

    public function copyBlockContext(int $id, BlockContextExporter $exporter): void
    {
        $block = Block::query()->findOrFail($id);
        $this->authorize('view', $block);

        $this->dispatch('block-context-copied', text: $exporter->export($block));
        session()->flash('status', __('Block context copied to clipboard.'));
    }

    public function duplicateBlock(int $id): void
    {
        $original = Block::query()->findOrFail($id);
        $this->authorize('create', Block::class);

        DB::transaction(function () use ($original): void {
            $new = $original->replicate();
            $new->forceFill([
                'uuid' => (string) Str::uuid(),
                'block_name' => $original->block_name.' ('.__('Copy').')',
                'block_slug' => $this->uniqueSlugFrom($original->block_slug.'-copy'),
                'is_active' => false,
                'is_managed' => false,
            ]);
            $new->save();
        });

        session()->flash('status', __('Block duplicated.'));
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $block = Block::query()->findOrFail($id);
        $this->authorize('update', $block);
        $block->update(['is_active' => ! $block->is_active]);
    }

    public function openPreview(int $id): void
    {
        $block = Block::query()->findOrFail($id);
        $this->authorize('view', $block);

        $this->previewBlockId = $id;
        $this->previewHtml = '';
        $this->previewError = '';

        try {
            $this->previewHtml = ContentParser::renderBlockCode(
                $block->code,
                0,
                is_string($block->custom_css) ? $block->custom_css : null,
                $block->block_slug
            );
        } catch (\Throwable $e) {
            $this->previewError = $e->getMessage();
        }

        $this->previewOpen = true;
    }

    public function closePreview(): void
    {
        $this->previewOpen = false;
        $this->previewHtml = '';
        $this->previewError = '';
        $this->previewBlockId = null;
    }

    protected function uniqueSlugFrom(string $base): string
    {
        $slug = $base;
        $n = 1;
        while (Block::query()->where('block_slug', $slug)->exists()) {
            $slug = $base.'-'.$n;
            $n++;
        }

        return $slug;
    }

    protected function resetForm(): void
    {
        $this->block_name = '';
        $this->block_slug = '';
        $this->description = '';
        $this->block_type = '';
        $this->code = '';
        $this->custom_css = '';
        $this->schema_json_input = '';
        $this->is_active = true;
        $this->service_choice = '';
        $this->editingManaged = false;
        $this->block_content = [];
        $this->resetValidation();
    }

    protected function loadBlockMarketingContent(?Block $block): void
    {
        if (! BlockContent::marketingCopyVisible($this->block_slug, $this->code)) {
            $this->block_content = [];

            return;
        }

        $schemaSlug = BlockContent::resolveSchemaSlug($this->block_slug, $this->code);
        $schema = BlockContent::schema($schemaSlug);
        $stored = [];
        if ($block !== null) {
            $settings = app(BlockSettingsEditor::class)->settings($block);
            $stored = is_array($settings['content'] ?? null) ? $settings['content'] : [];
        }

        $this->block_content = [];
        foreach ($schema as $key => $field) {
            $this->block_content[$key] = (string) ($stored[$key] ?? ($field['default'] ?? ''));
        }
    }
}
