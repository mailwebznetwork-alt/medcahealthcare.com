<?php

namespace App\Livewire\SiteArchitect;

use App\Models\Block;
use App\Models\Blog;
use App\Models\Page;
use App\Support\BlockContent;
use App\Livewire\Concerns\HandlesArchitectFlexibleSave;
use App\Livewire\SiteArchitect\Concerns\InteractsWithPageSectionPicker;
use App\Support\ArchitectSaveBypass;
use App\Services\DynamicModules\DynamicModuleInsertCatalog;
use App\Services\Integrations\OutboundWebhookDispatcher;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Blogs extends Component
{
    use AuthorizesRequests;
    use HandlesArchitectFlexibleSave;
    use InteractsWithPageSectionPicker;
    use WithFileUploads;
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

    public string $excerpt = '';

    public string $author_name = '';

    public ?string $published_at_input = null;

    public bool $is_published = false;

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

    /** @var list<array{type: string, slug: string}> */
    public array $contentParts = [];

    public bool $blockModalOpen = false;

    public int $previewRefreshNonce = 0;

    public ?string $blockEditingSlug = null;

    public string $block_name = '';

    public string $block_slug = '';

    public string $block_code = '';

    public string $block_custom_css = '';

    public string $module_choice = '';

    /** Current stored path on public disk (not a Livewire upload). */
    public ?string $featured_image_path = null;

    public $featured_image_upload = null;

    public function render()
    {
        $blogs = Blog::query()->latest()->paginate(12);

        $moduleOptions = app(DynamicModuleInsertCatalog::class)->forDropdown();

        $blockNameMap = [];
        if ($this->mode === 'form' && $this->contentParts !== []) {
            $slugs = collect($this->contentParts)
                ->filter(fn ($p) => ($p['type'] ?? '') === 'block')
                ->pluck('slug')
                ->unique()
                ->values()
                ->all();
            if ($slugs !== []) {
                $blockNameMap = Block::query()->whereIn('block_slug', $slugs)->pluck('block_name', 'block_slug')->all();
            }
        }

        $productionPreviewUrl = null;
        if ($this->mode === 'form' && $this->editingId !== null) {
            $editingBlog = Blog::query()->find($this->editingId);
            if ($editingBlog !== null) {
                $productionPreviewUrl = route('site-architect.blogs.preview', $editingBlog);
            }
        }

        return view('livewire.site-architect.blogs', [
            'blogs' => $blogs,
            'moduleOptions' => $moduleOptions,
            'blockNameMap' => $blockNameMap,
            'productionPreviewUrl' => $productionPreviewUrl,
            'sectionPickerGroups' => $this->sectionPickerOpen ? $this->sectionPickerGroups() : [],
            'sectionPickerCategories' => config('page_builder_sections.picker_categories', []),
            'canUseDeveloperBlockTools' => $this->canUseDeveloperBlockTools(),
        ]);
    }

    public function startCreate(): void
    {
        $this->authorize('create', Blog::class);

        $this->resetForm();
        $this->mode = 'form';
        $this->editingId = null;
    }

    public function startEdit(int $id): void
    {
        $blog = Blog::query()->findOrFail($id);
        $this->authorize('update', $blog);

        $this->resetForm();
        $this->editingId = $id;
        $this->mode = 'form';

        $this->title = $blog->title;
        $this->slug = $blog->slug;
        $this->excerpt = (string) ($blog->excerpt ?? '');
        $this->author_name = (string) ($blog->author_name ?? '');
        $this->published_at_input = $blog->published_at !== null
            ? $blog->published_at->format('Y-m-d\TH:i')
            : '';
        $this->is_published = $blog->is_published;
        $this->featured_image_path = $blog->featured_image;
        $this->meta_title = (string) ($blog->meta_title ?? '');
        $this->meta_description = (string) ($blog->meta_description ?? '');
        $this->keywords = (string) ($blog->keywords ?? '');
        $this->h1 = (string) ($blog->h1 ?? '');
        $this->h2 = (string) ($blog->h2 ?? '');
        $this->h3 = (string) ($blog->h3 ?? '');
        $this->h4 = (string) ($blog->h4 ?? '');
        $this->h5 = (string) ($blog->h5 ?? '');
        $this->h6 = (string) ($blog->h6 ?? '');
        $this->aeo_question = (string) ($blog->aeo_question ?? '');
        $this->aeo_answer = (string) ($blog->aeo_answer ?? '');
        $this->schema_json_input = $blog->schema_json !== null
            ? json_encode($blog->schema_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';

        $this->contentParts = Page::parseContentTokens($blog->content);
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

    public function confirmArchitectOverwriteSave(): void
    {
        $this->architectOverwriteApproved = true;
        $this->saveBlog();
    }

    public function confirmArchitectIncompleteSave(): void
    {
        $this->architectIncompleteSaveApproved = true;
        $this->saveBlog();
    }

    public function saveBlog(): void
    {
        $wasCreating = $this->editingId === null;

        if ($this->architectSaveBypassEligible() && $this->architectIncompleteSaveApproved) {
            if (trim($this->title) === '') {
                $this->title = __('Untitled blog post');
            }
            if (trim($this->slug) === '') {
                $this->slug = ArchitectSaveBypass::defaultBlockSlug($this->title, 'blog-'.Str::lower(Str::random(8)));
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
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
            'excerpt' => ['nullable', 'string'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'published_at_input' => ['nullable', 'date'],
            'is_published' => ['boolean'],
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
            'featured_image_upload' => ['nullable', 'image', 'max:4096'],
        ], ['title', 'slug'])) {
            return;
        }

        if (! $this->assertArchitectUniqueAvailable(Blog::class, 'slug', $this->slug, $this->editingId, 'title')) {
            return;
        }

        $publishedAt = null;
        if ($this->published_at_input !== null && trim((string) $this->published_at_input) !== '') {
            $publishedAt = Carbon::parse($this->published_at_input);
        }

        $featuredPath = $this->featured_image_path;
        if ($this->featured_image_upload !== null) {
            $featuredPath = $this->featured_image_upload->store('blogs', 'public');
        }

        $content = Page::buildContentFromParts($this->contentParts);

        $data = [
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt !== '' ? $this->excerpt : null,
            'content' => $content,
            'featured_image' => $featuredPath,
            'author_name' => $this->author_name !== '' ? $this->author_name : null,
            'published_at' => $publishedAt,
            'meta_title' => $this->meta_title !== '' ? $this->meta_title : null,
            'meta_description' => $this->meta_description !== '' ? $this->meta_description : null,
            'keywords' => $this->keywords !== '' ? $this->keywords : null,
            'h1' => $this->h1 !== '' ? $this->h1 : null,
            'h2' => $this->h2 !== '' ? $this->h2 : null,
            'h3' => $this->h3 !== '' ? $this->h3 : null,
            'h4' => $this->h4 !== '' ? $this->h4 : null,
            'h5' => $this->h5 !== '' ? $this->h5 : null,
            'h6' => $this->h6 !== '' ? $this->h6 : null,
            'aeo_question' => $this->aeo_question !== '' ? $this->aeo_question : null,
            'aeo_answer' => $this->aeo_answer !== '' ? $this->aeo_answer : null,
            'schema_json' => $schemaDecoded,
            'is_published' => $this->is_published,
        ];

        $savedBlogId = null;

        DB::transaction(function () use ($data, $featuredPath, &$savedBlogId): void {
            if ($this->editingId === null) {
                $this->authorize('create', Blog::class);
                $blog = Blog::query()->create($data);
                $savedBlogId = $blog->id;
            } else {
                $blog = Blog::query()->findOrFail($this->editingId);
                $this->authorize('update', $blog);
                $old = $blog->featured_image;
                if (
                    $old
                    && ! Str::startsWith($old, ['http://', 'https://'])
                    && ($old !== $featuredPath)
                    && Storage::disk('public')->exists($old)
                ) {
                    Storage::disk('public')->delete($old);
                }
                $blog->update($data);
                $savedBlogId = $blog->id;
            }
        });

        if ($savedBlogId !== null) {
            $blog = Blog::query()->find($savedBlogId);
            if ($blog !== null && $blog->is_published) {
                app(OutboundWebhookDispatcher::class)->dispatch('blog.published', [
                    'blog_id' => $blog->id,
                    'slug' => $blog->slug,
                    'title' => $blog->title,
                    'published_at' => $blog->published_at?->toIso8601String(),
                ]);
            }
        }

        $this->featured_image_upload = null;

        session()->flash('status', __('Blog saved.'));
        $this->resetArchitectSaveFlags();

        if ($wasCreating) {
            $this->mode = 'list';
            $this->editingId = null;
            $this->resetForm();
            $this->resetPage();
        } elseif ($savedBlogId !== null) {
            $this->previewRefreshNonce++;
            $this->startEdit($savedBlogId);
        } else {
            $this->mode = 'list';
            $this->editingId = null;
            $this->resetForm();
            $this->resetPage();
        }
    }

    public function deleteBlog(int $id): void
    {
        $blog = Blog::query()->findOrFail($id);
        $this->authorize('delete', $blog);
        if ($blog->featured_image && ! Str::startsWith($blog->featured_image, ['http://', 'https://'])) {
            Storage::disk('public')->delete($blog->featured_image);
        }
        $blog->delete();
        session()->flash('status', __('Blog deleted.'));
        $this->resetPage();
    }

    public function duplicateBlog(int $id): void
    {
        $original = Blog::query()->findOrFail($id);
        $this->authorize('create', Blog::class);

        DB::transaction(function () use ($original): void {
            $new = $original->replicate();
            $new->forceFill(['uuid' => (string) Str::uuid()]);
            $new->title = $original->title.' ('.__('Copy').')';
            $baseSlug = $original->slug.'-copy';
            $new->slug = $baseSlug;
            $n = 1;
            while (Blog::query()->where('slug', $new->slug)->exists()) {
                $new->slug = $baseSlug.'-'.$n;
                $n++;
            }
            $new->is_published = false;
            $new->published_at = null;

            if ($original->featured_image && ! Str::startsWith($original->featured_image, ['http://', 'https://'])) {
                if (Storage::disk('public')->exists($original->featured_image)) {
                    $ext = pathinfo($original->featured_image, PATHINFO_EXTENSION);
                    $newPath = 'blogs/'.Str::uuid().($ext !== '' ? '.'.$ext : '');
                    Storage::disk('public')->copy($original->featured_image, $newPath);
                    $new->featured_image = $newPath;
                }
            }

            $new->save();
        });

        session()->flash('status', __('Blog duplicated.'));
        $this->resetPage();
    }

    public function togglePublished(int $id): void
    {
        $blog = Blog::query()->findOrFail($id);
        $this->authorize('update', $blog);
        $blog->update(['is_published' => ! $blog->is_published]);
        $blog->refresh();

        if ($blog->is_published) {
            app(OutboundWebhookDispatcher::class)->dispatch('blog.published', [
                'blog_id' => $blog->id,
                'slug' => $blog->slug,
                'title' => $blog->title,
                'published_at' => $blog->published_at?->toIso8601String(),
            ]);
        }
    }

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
            $this->block_code = $block->code;
            $this->block_custom_css = (string) ($block->custom_css ?? '');
        } else {
            $this->block_name = '';
            $this->block_slug = '';
            $this->block_code = '';
            $this->block_custom_css = '';
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
        if ($this->blockEditingSlug !== null) {
            $existing = Block::query()->where('block_slug', $this->blockEditingSlug)->first();
            if ($existing?->is_managed) {
                $this->addError('block_code', __('Managed blocks cannot be edited here. Use Blocks Studio.'));

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

    public function removeFeaturedImage(): void
    {
        if ($this->featured_image_path && ! Str::startsWith($this->featured_image_path, ['http://', 'https://'])) {
            Storage::disk('public')->delete($this->featured_image_path);
        }
        $this->featured_image_path = null;
    }

    protected function resetForm(): void
    {
        $this->title = '';
        $this->slug = '';
        $this->excerpt = '';
        $this->author_name = '';
        $this->published_at_input = '';
        $this->is_published = false;
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
        $this->contentParts = [];
        $this->module_choice = '';
        $this->featured_image_path = null;
        $this->featured_image_upload = null;
        $this->resetValidation();
    }
}
