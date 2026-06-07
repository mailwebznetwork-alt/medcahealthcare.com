<?php

namespace App\Livewire\Media;

use App\Models\Media;
use App\Services\Media\MediaUploadProcessor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MediaPickerModal extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    public bool $open = false;

    public ?string $activeKey = null;

    public ?int $highlightId = null;

    public string $search = '';

    public string $filter_type = 'image';

    public string $filter_category = '';

    public string $filter_tag = '';

    /** @var array<int, mixed> */
    public array $uploads = [];

    #[On('open-media-picker')]
    public function openPicker(string $key, ?int $selectedId = null): void
    {
        $this->authorize('create', Media::class);
        $this->activeKey = $key;
        $this->highlightId = $selectedId;
        $this->open = true;
        $this->resetPage();
    }

    public function close(): void
    {
        $this->open = false;
        $this->activeKey = null;
        $this->uploads = [];
    }

    public function select(int $mediaId): void
    {
        $this->authorize('view', Media::query()->findOrFail($mediaId));
        if ($this->activeKey === null) {
            return;
        }
        $this->dispatch('media-selected', key: $this->activeKey, mediaId: $mediaId);
        $this->close();
    }

    public function updatedUploads(): void
    {
        $this->validate([
            'uploads' => ['array'],
            'uploads.*' => ['file', 'max:'.(int) config('media.max_upload_kb', 51200)],
        ]);
        $this->authorize('create', Media::class);

        foreach ($this->uploads as $file) {
            try {
                $media = app(MediaUploadProcessor::class)->process($file, Auth::id(), 'media-picker');
                if ($this->activeKey !== null) {
                    $this->dispatch('media-selected', key: $this->activeKey, mediaId: $media->id);
                    $this->close();

                    return;
                }
            } catch (\Throwable $e) {
                session()->flash('picker_error', $e->getMessage());
            }
        }
        $this->uploads = [];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter_type(): void
    {
        $this->resetPage();
    }

    public function updatingFilter_category(): void
    {
        $this->resetPage();
    }

    public function updatingFilter_tag(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Media::query()->withCount('usages')->latest();

        if ($this->filter_type !== '') {
            $query->where('file_type', $this->filter_type);
        }
        if ($this->filter_category !== '') {
            $query->where('category', $this->filter_category);
        }
        if ($this->filter_tag !== '') {
            $query->whereJsonContains('tags', $this->filter_tag);
        }
        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term): void {
                $q->where('file_name', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhere('alt_text', 'like', $term);
            });
        }

        $categories = Media::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('livewire.media.media-picker-modal', [
            'items' => $query->paginate(12),
            'categories' => $categories,
        ]);
    }
}
