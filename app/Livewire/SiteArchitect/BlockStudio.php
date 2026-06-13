<?php

namespace App\Livewire\SiteArchitect;

use App\Models\Block;
use App\Models\Media;
use App\Policies\DeploymentEnginePolicy;
use App\Services\Media\MediaUploadProcessor;
use App\Services\Media\MediaUsageTracker;
use Livewire\Attributes\On;
use App\Support\BlockContent;
use App\Services\Deployment\BlockSettingsEditor;
use App\Services\SiteArchitect\PageSectionCatalog;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class BlockStudio extends Component
{
    use WithFileUploads;

    public string $activePanel = 'content';

    /** @var array<string, string> */
    public array $content = [];

    public string $block_slug = '';

    public string $style_variant = 'style_1';

    /** @var array<string, string> */
    public array $media = [];

    /** @var array<string, int|string> */
    public array $media_refs = [];

    /** @var array<string, mixed> */
    public array $section = [];

    /** @var array<string, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null> */
    public array $uploads = [];

    public ?string $preview_html = null;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        abort_unless(app(DeploymentEnginePolicy::class)->manageBlockPresets(auth()->user()), 403);

        $requested = request()->query('block');
        if (is_string($requested) && $requested !== '') {
            $exists = Block::query()->where('block_slug', $requested)->where('is_active', true)->exists();
            if ($exists) {
                $this->block_slug = $requested;
                $this->loadBlock();

                return;
            }
        }

        $first = Block::query()->where('is_active', true)->orderBy('block_slug')->value('block_slug');
        if (is_string($first)) {
            $this->block_slug = $first;
            $this->loadBlock();
        }
    }

    public function updatedBlockSlug(): void
    {
        $this->loadBlock();
    }

    public function loadBlock(): void
    {
        $block = $this->selectedBlock();
        if ($block === null) {
            return;
        }

        $settings = app(BlockSettingsEditor::class)->settings($block);
        $this->style_variant = (string) ($settings['style_variant'] ?? 'style_1');
        $this->media = is_array($settings['media'] ?? null) ? array_map('strval', $settings['media']) : [];
        $this->media_refs = is_array($settings['media_refs'] ?? null) ? $settings['media_refs'] : [];
        $this->section = is_array($settings['section'] ?? null) ? $settings['section'] : [];
        $storedContent = is_array($settings['content'] ?? null) ? $settings['content'] : [];
        $this->content = [];
        $schemaSlug = BlockContent::resolveSchemaSlug((string) $block->block_slug, (string) $block->code);
        foreach (BlockContent::schema($schemaSlug) as $key => $field) {
            $this->content[$key] = (string) ($storedContent[$key] ?? ($field['default'] ?? ''));
        }
        $this->activePanel = $this->content !== [] ? 'content' : 'media';

        foreach (app(BlockSettingsEditor::class)->mediaSlotsForBlock($block) as $slot) {
            if (! array_key_exists($slot, $this->media)) {
                $this->media[$slot] = '';
            }
            if (! array_key_exists($slot, $this->media_refs)) {
                $this->media_refs[$slot] = '';
            }
        }

        foreach (app(BlockSettingsEditor::class)->sectionControlKeys() as $key) {
            if (! array_key_exists($key, $this->section)) {
                $this->section[$key] = str_starts_with($key, 'visibility_') ? true : '';
            }
        }
    }

    public function saveDraft(BlockSettingsEditor $editor): void
    {
        $block = $this->selectedBlock();
        if ($block === null) {
            $this->errorMessage = __('Select a block.');

            return;
        }

        $processor = app(MediaUploadProcessor::class);
        $tracker = app(MediaUsageTracker::class);
        foreach ($this->uploads as $slot => $file) {
            if ($file === null) {
                continue;
            }
            $media = $processor->process($file, auth()->id(), 'blocks');
            $this->media[$slot] = $media->referencePath();
            $tracker->detachAllFor($block, (string) $slot);
            $tracker->attach($media, $block, (string) $slot, $block->block_slug.' · '.$slot);
        }

        foreach ($editor->mediaSlotsForBlock($block) as $slot) {
            $path = trim((string) ($this->media[$slot] ?? ''));
            $ref = $this->media_refs[$slot] ?? '';
            if ($path === '' && ($ref === '' || $ref === 0)) {
                $tracker->detachAllFor($block, $slot);
            }
        }

        $editor->save($block, [
            'style_variant' => $this->style_variant,
            'media' => $this->media,
            'media_refs' => $this->media_refs,
            'section' => $this->section,
            'content' => $this->content,
        ]);

        $this->uploads = [];
        $this->statusMessage = __('Section settings saved.');
        $this->errorMessage = null;
    }

    public function preview(BlockSettingsEditor $editor): void
    {
        $block = $this->selectedBlock();
        if ($block === null) {
            return;
        }

        $editor->save($block, [
            'style_variant' => $this->style_variant,
            'media' => $this->media,
            'media_refs' => $this->media_refs,
            'section' => $this->section,
            'content' => $this->content,
        ]);

        $this->preview_html = $editor->previewHtml($block->fresh());
    }

    public function removeMedia(string $slot): void
    {
        $this->media[$slot] = '';
        $this->media_refs[$slot] = '';
        unset($this->uploads[$slot]);
    }

    public function openMediaPicker(string $slot): void
    {
        $block = $this->selectedBlock();
        if ($block === null) {
            return;
        }
        $key = 'block-'.$block->block_slug.'-'.$slot;
        $selected = is_numeric($this->media_refs[$slot] ?? null) ? (int) $this->media_refs[$slot] : null;
        $this->dispatch('open-media-picker', key: $key, selectedId: $selected);
    }

    #[On('media-selected')]
    public function onMediaSelected(string $key, int $mediaId): void
    {
        $block = $this->selectedBlock();
        if ($block === null || ! str_starts_with($key, 'block-'.$block->block_slug.'-')) {
            return;
        }
        $slot = substr($key, strlen('block-'.$block->block_slug.'-'));
        $media = Media::query()->find($mediaId);
        if ($media === null) {
            return;
        }
        $tracker = app(MediaUsageTracker::class);
        $tracker->detachAllFor($block, $slot);
        $this->media_refs[$slot] = $media->id;
        $this->media[$slot] = $media->referencePath();
        $tracker->attach($media, $block, $slot, $block->block_slug.' · '.$slot);
    }

    public function render(BlockSettingsEditor $editor): View
    {
        $block = $this->selectedBlock();

        $catalog = app(PageSectionCatalog::class);

        return view('livewire.site-architect.block-studio', [
            'sectionPickerGroups' => $catalog->groupedForPicker(),
            'sectionDisplayName' => $block ? $catalog->displayNameForSlug((string) $block->block_slug) : '',
            'mediaSlots' => $block ? $editor->mediaSlotsForBlock($block) : [],
            'sectionKeys' => $editor->sectionControlKeys(),
            'styleVariants' => $editor->styleVariants(),
            'contentSchema' => $block ? $editor->contentSchemaForBlock($block) : [],
        ]);
    }

    private function selectedBlock(): ?Block
    {
        if ($this->block_slug === '') {
            return null;
        }

        return Block::query()->where('block_slug', $this->block_slug)->first();
    }
}
