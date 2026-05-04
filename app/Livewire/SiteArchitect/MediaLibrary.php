<?php

namespace App\Livewire\SiteArchitect;

use App\Models\Media;
use App\Services\Media\MediaUploadProcessor;
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

    public string $edit_description = '';

    public function updatedUploads(): void
    {
        $this->validate([
            'uploads' => ['array'],
            'uploads.*' => ['file', 'max:51200'],
        ]);

        $this->authorize('create', Media::class);

        $processor = app(MediaUploadProcessor::class);
        $errors = [];

        foreach ($this->uploads as $file) {
            try {
                $processor->process($file, Auth::id());
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
        $this->edit_description = (string) ($media->description ?? '');
    }

    public function closeDetail(): void
    {
        $this->selectedId = null;
        $this->edit_title = '';
        $this->edit_alt_text = '';
        $this->edit_description = '';
        $this->resetValidation();
    }

    public function saveMetadata(): void
    {
        $media = Media::query()->findOrFail($this->selectedId ?? 0);
        $this->authorize('update', $media);

        $rules = [
            'edit_title' => ['nullable', 'string', 'max:255'],
            'edit_description' => ['nullable', 'string'],
        ];

        if ($media->file_type === 'image') {
            $rules['edit_alt_text'] = ['required', 'string', 'max:500'];
        } else {
            $rules['edit_alt_text'] = ['nullable', 'string', 'max:500'];
        }

        $this->validate($rules);

        $media->update([
            'title' => $this->edit_title !== '' ? $this->edit_title : null,
            'alt_text' => $this->edit_alt_text !== '' ? $this->edit_alt_text : null,
            'description' => $this->edit_description !== '' ? $this->edit_description : null,
        ]);

        session()->flash('status', __('Metadata saved.'));
    }

    public function deleteMedia(int $id): void
    {
        $media = Media::query()->findOrFail($id);
        $this->authorize('delete', $media);

        if ($this->selectedId === $id) {
            $this->closeDetail();
        }

        $media->delete();
        session()->flash('status', __('Media deleted.'));
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
                    ->orWhere('alt_text', 'like', $term);
            });
        }

        $items = $query->paginate(24);

        $selected = $this->selectedId !== null
            ? Media::query()->find($this->selectedId)
            : null;

        return view('livewire.site-architect.media-library', [
            'items' => $items,
            'selected' => $selected,
        ]);
    }
}
