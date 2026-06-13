<?php

namespace App\Livewire\SiteArchitect;

use App\Models\Media;
use App\Services\Media\MediaGeminiSuggestions;
use App\Services\Media\MediaImageSeoScorer;
use App\Services\Media\MediaUploadProcessor;
use App\Services\Media\MediaUsageTracker;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MediaLibrary extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    /** @var array<int, mixed> */
    public array $uploads = [];

    public string $search = '';

    public string $filter_type = '';

    public ?int $selectedId = null;

    public string $edit_title = '';

    public string $edit_alt_text = '';

    public string $edit_caption = '';

    public string $edit_description = '';

    public string $edit_category = '';

    public string $edit_tags = '';

    public function updatedUploads(): void
    {
        $this->validate([
            'uploads' => ['array'],
            'uploads.*' => ['file', 'max:'.(int) config('media.max_upload_kb', 51200)],
        ]);

        $this->authorize('create', Media::class);

        $processor = app(MediaUploadProcessor::class);
        $errors = [];

        foreach ($this->uploads as $file) {
            try {
                $processor->process($file, Auth::id(), 'media-library');
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        $this->uploads = [];
        $this->resetPage();

        if ($errors !== []) {
            session()->flash('error', implode(' ', $errors));
        } else {
            session()->flash('status', __('Upload complete.'));
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter_type(): void
    {
        $this->resetPage();
    }

    public function selectMedia(int $id): void
    {
        $media = Media::query()->findOrFail($id);
        $this->authorize('view', $media);

        $this->selectedId = $id;
        $this->edit_title = (string) ($media->title ?? '');
        $this->edit_alt_text = (string) ($media->alt_text ?? '');
        $this->edit_caption = (string) ($media->caption ?? '');
        $this->edit_description = (string) ($media->description ?? '');
        $this->edit_category = (string) ($media->category ?? '');
        $tags = is_array($media->tags) ? $media->tags : [];
        $this->edit_tags = implode(', ', $tags);
    }

    public function closeDetail(): void
    {
        $this->selectedId = null;
        $this->edit_title = '';
        $this->edit_alt_text = '';
        $this->edit_caption = '';
        $this->edit_description = '';
        $this->edit_category = '';
        $this->edit_tags = '';
        $this->resetValidation();
    }

    public function saveMetadata(): void
    {
        $media = Media::query()->findOrFail($this->selectedId ?? 0);
        $this->authorize('update', $media);

        $rules = [
            'edit_title' => ['nullable', 'string', 'max:255'],
            'edit_caption' => ['nullable', 'string', 'max:500'],
            'edit_description' => ['nullable', 'string'],
            'edit_category' => ['nullable', 'string', 'max:64'],
            'edit_tags' => ['nullable', 'string', 'max:500'],
        ];

        if ($media->file_type === 'image') {
            $rules['edit_alt_text'] = ['required', 'string', 'max:500'];
        } else {
            $rules['edit_alt_text'] = ['nullable', 'string', 'max:500'];
        }

        $this->validate($rules);

        $tags = array_values(array_filter(array_map(
            static fn (string $t): string => trim($t),
            explode(',', $this->edit_tags)
        ), static fn (string $t): bool => $t !== ''));

        $media->update([
            'title' => $this->edit_title !== '' ? $this->edit_title : null,
            'alt_text' => $this->edit_alt_text !== '' ? $this->edit_alt_text : null,
            'caption' => $this->edit_caption !== '' ? $this->edit_caption : null,
            'description' => $this->edit_description !== '' ? $this->edit_description : null,
            'category' => $this->edit_category !== '' ? $this->edit_category : null,
            'tags' => $tags === [] ? null : $tags,
        ]);

        app(MediaImageSeoScorer::class)->persist($media);

        session()->flash('status', __('Metadata saved.'));
    }

    public function suggestWithGemini(): void
    {
        $media = Media::query()->findOrFail($this->selectedId ?? 0);
        $this->authorize('update', $media);

        $suggestions = app(MediaGeminiSuggestions::class)->suggest($media, $media->file_name);
        if ($suggestions === null) {
            session()->flash('error', __('AI suggestions unavailable. Check GEMINI_API_KEY.'));

            return;
        }

        if (filled($suggestions['alt'])) {
            $this->edit_alt_text = $suggestions['alt'];
        }
        if (filled($suggestions['title'])) {
            $this->edit_title = $suggestions['title'];
        }
        if (filled($suggestions['caption'])) {
            $this->edit_caption = $suggestions['caption'];
        }
        if (filled($suggestions['description'])) {
            $this->edit_description = $suggestions['description'];
        }

        session()->flash('status', __('AI suggestions applied — review and save.'));
    }

    public function deleteMedia(int $id, bool $force = false): void
    {
        $media = Media::query()->findOrFail($id);
        $this->authorize('delete', $media);

        $tracker = app(MediaUsageTracker::class);

        if ($tracker->isInUse($media)) {
            if (! $force) {
                session()->flash(
                    'error',
                    __('This asset is used in :count place(s). Remove it from sections first, or use “Detach & delete”.', [
                        'count' => count($tracker->referencesFor($media)),
                    ])
                );

                return;
            }

            $released = $tracker->releaseAllReferencesFor($media);
            $detachNote = __('Detached from :count section(s).', ['count' => $released]);
        } else {
            $detachNote = null;
        }

        if ($this->selectedId === $id) {
            $this->closeDetail();
        }

        $media->delete();
        session()->flash(
            'status',
            isset($detachNote) ? $detachNote.' '.__('Media deleted.') : __('Media deleted.')
        );
        $this->resetPage();
    }

    public function render()
    {
        $query = Media::query()->latest();

        if ($this->filter_type !== '') {
            $query->where('file_type', $this->filter_type);
        }

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term): void {
                $q->where('file_name', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhere('alt_text', 'like', $term)
                    ->orWhere('caption', 'like', $term);
            });
        }

        $items = $query->paginate(24);

        $selected = $this->selectedId !== null
            ? Media::query()->withCount('usages')->find($this->selectedId)
            : null;

        $usageReferences = [];
        $seoRecommendations = [];
        if ($selected) {
            $usageReferences = app(MediaUsageTracker::class)->referencesFor($selected);
            $seoRecommendations = app(MediaImageSeoScorer::class)->recommendations($selected);
        }

        return view('livewire.site-architect.media-library', [
            'items' => $items,
            'selected' => $selected,
            'usageReferences' => $usageReferences,
            'seoRecommendations' => $seoRecommendations,
        ]);
    }
}
