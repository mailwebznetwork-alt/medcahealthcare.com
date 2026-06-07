<?php

namespace App\Livewire\Media;

use Livewire\Attributes\On;
use Livewire\Component;

class MediaGalleryPickerField extends Component
{
    /** @var list<int> */
    public array $ids = [];

    public string $pickerKey = 'gallery-add';

    public function openPicker(): void
    {
        $this->dispatch('open-media-picker', key: $this->pickerKey, selectedId: null);
    }

    #[On('media-selected')]
    public function onSelected(string $key, int $mediaId): void
    {
        if ($key !== $this->pickerKey) {
            return;
        }
        if (! in_array($mediaId, $this->ids, true)) {
            $this->ids[] = $mediaId;
        }
    }

    public function remove(int $mediaId): void
    {
        $this->ids = array_values(array_filter(
            $this->ids,
            static fn (int $id): bool => $id !== $mediaId
        ));
    }

    public function render()
    {
        return view('livewire.media.media-gallery-picker-field');
    }
}
