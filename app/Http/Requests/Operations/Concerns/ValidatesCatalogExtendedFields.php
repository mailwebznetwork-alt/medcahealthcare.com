<?php

namespace App\Http\Requests\Operations\Concerns;

use App\Http\Requests\Operations\Services\Concerns\ValidatesServiceExtendedFields;

trait ValidatesCatalogExtendedFields
{
    use ValidatesServiceExtendedFields;

    /**
     * @return array<string, mixed>
     */
    protected function extendedCatalogFieldRules(): array
    {
        return array_merge($this->extendedServiceFieldRules(), [
            'featured_image' => ['nullable', 'image', 'max:5120'],
            'gallery_files' => ['nullable', 'array'],
            'gallery_files.*' => ['image', 'max:5120'],
            'remove_gallery' => ['nullable', 'array'],
            'remove_gallery.*' => ['string', 'max:500'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'icon_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'picker_gallery_media_ids' => ['nullable', 'array'],
            'picker_gallery_media_ids.*' => ['integer', 'exists:media,id'],
        ]);
    }
}
