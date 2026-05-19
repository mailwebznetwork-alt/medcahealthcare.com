<?php

namespace App\Livewire\SiteArchitect;

use App\Models\Block;
use App\Services\ContentParser;
use App\Services\SiteArchitect\ServiceInsertCatalog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class BlockFactory extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $mode = 'list';

    public ?int $editingId = null;

    public string $block_name = '';

    public string $block_slug = '';

    public string $description = '';

    public string $block_type = '';

    public string $code = '';

    public string $schema_json_input = '';

    public bool $is_active = true;

    public bool $previewOpen = false;

    public string $previewHtml = '';

    public string $previewError = '';

    public ?int $previewBlockId = null;

    public string $service_choice = '';

    public int $serviceCatalogNonce = 0;

    public function mount(): void
    {
        if (request()->query('create') === '1') {
            $this->startCreate();
        }
    }

    public function render()
    {
        $blocks = Block::query()->latest()->paginate(15);

        $typesByGroup = config('block_factory.types_by_group', []);
        $allowedTypes = collect($typesByGroup)->flatten()->unique()->values()->all();

        $services = $this->mode === 'form'
            ? app(ServiceInsertCatalog::class)->forDropdown()
            : collect();

        return view('livewire.site-architect.block-factory', [
            'blocks' => $blocks,
            'typesByGroup' => $typesByGroup,
            'allowedTypes' => $allowedTypes,
            'services' => $services,
            'serviceCatalogNonce' => $this->serviceCatalogNonce,
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
        $this->serviceCatalogNonce++;
        $this->schema_json_input = $block->schema_json !== null
            ? json_encode($block->schema_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';
        $this->is_active = $block->is_active;
    }

    public function cancelForm(): void
    {
        $this->mode = 'list';
        $this->editingId = null;
        $this->resetForm();
    }

    public function updatedBlockName(string $value): void
    {
        if ($this->editingId === null && $this->block_slug === '') {
            $this->block_slug = Str::slug($value);
        }
    }

    public function saveBlock(): void
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

        $this->validate([
            'block_name' => ['required', 'string', 'max:255'],
            'block_slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('blocks', 'block_slug')->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string'],
            'block_type' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string'],
            'schema_json_input' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $data = [
            'block_name' => $this->block_name,
            'block_slug' => $this->block_slug,
            'description' => $this->description !== '' ? $this->description : null,
            'block_type' => $this->block_type !== '' ? $this->block_type : null,
            'code' => $this->code,
            'schema_json' => $schemaDecoded,
            'is_active' => $this->is_active,
        ];

        DB::transaction(function () use ($data): void {
            if ($this->editingId === null) {
                $this->authorize('create', Block::class);
                Block::query()->create($data);
            } else {
                $block = Block::query()->findOrFail($this->editingId);
                $this->authorize('update', $block);
                $block->update($data);
            }
        });

        session()->flash('status', __('Block saved.'));
        $this->mode = 'list';
        $this->editingId = null;
        $this->resetForm();
        $this->resetPage();
    }

    public function deleteBlock(int $id): void
    {
        $block = Block::query()->findOrFail($id);
        $this->authorize('delete', $block);
        $block->delete();

        session()->flash('status', __('Block deleted.'));
        $this->resetPage();
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
            $this->previewHtml = ContentParser::renderBlockCode($block->code);
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
        $this->schema_json_input = '';
        $this->is_active = true;
        $this->service_choice = '';
        $this->resetValidation();
    }
}
