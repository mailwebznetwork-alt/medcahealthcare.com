<?php

namespace App\Livewire\Media;

use App\Models\Media;
use Livewire\Attributes\On;
use Livewire\Component;

class MediaPickerField extends Component
{
    public string $fieldName;

    public ?int $value = null;

    public string $label;

    public string $pickerKey;

    public function mount(string $fieldName, ?int $value = null, string $label = ''): void
    {
        $this->fieldName = $fieldName;
        $this->value = $value;
        $this->label = $label !== '' ? $label : __('Media');
        $this->pickerKey = 'picker-'.$fieldName;
    }

    public function openPicker(): void
    {
        $this->dispatch('open-media-picker', key: $this->pickerKey, selectedId: $this->value);
    }

    #[On('media-selected')]
    public function onSelected(string $key, int $mediaId): void
    {
        if ($key !== $this->pickerKey) {
            return;
        }
        $this->value = $mediaId;
    }

    public function clear(): void
    {
        $this->value = null;
    }

    public function render()
    {
        $media = $this->value ? Media::query()->withCount('usages')->find($this->value) : null;

        return view('livewire.media.media-picker-field', [
            'media' => $media,
        ]);
    }
}
