<?php

namespace App\Services\Media;

use App\Models\Block;
use App\Models\Media;
use App\Models\MediaUsage;
use Illuminate\Database\Eloquent\Model;

class MediaUsageTracker
{
    public function attach(Media $media, Model $usable, string $field, ?string $label = null): void
    {
        MediaUsage::query()->updateOrCreate(
            [
                'media_id' => $media->id,
                'usable_type' => $usable->getMorphClass(),
                'usable_id' => $usable->getKey(),
                'field' => $field,
            ],
            ['label' => $label ?? $this->defaultLabel($usable, $field)]
        );
    }

    public function detach(Media $media, Model $usable, string $field): void
    {
        MediaUsage::query()
            ->where('media_id', $media->id)
            ->where('usable_type', $usable->getMorphClass())
            ->where('usable_id', $usable->getKey())
            ->where('field', $field)
            ->delete();
    }

    public function detachAllFor(Model $usable, string $field): void
    {
        MediaUsage::query()
            ->where('usable_type', $usable->getMorphClass())
            ->where('usable_id', $usable->getKey())
            ->where('field', $field)
            ->delete();
    }

    /**
     * @return list<array{label: string, type: string, id: int, field: string}>
     */
    public function referencesFor(Media $media): array
    {
        return MediaUsage::query()
            ->where('media_id', $media->id)
            ->orderBy('usable_type')
            ->orderBy('field')
            ->get()
            ->map(function (MediaUsage $usage): array {
                return [
                    'label' => (string) ($usage->label ?? $usage->field),
                    'type' => class_basename($usage->usable_type),
                    'id' => (int) $usage->usable_id,
                    'field' => $usage->field,
                ];
            })
            ->all();
    }

    public function isInUse(Media $media): bool
    {
        return MediaUsage::query()->where('media_id', $media->id)->exists();
    }

    /**
     * Clears block/page settings references and removes usage rows so media can be deleted.
     */
    public function releaseAllReferencesFor(Media $media): int
    {
        $released = 0;

        MediaUsage::query()
            ->where('media_id', $media->id)
            ->orderBy('id')
            ->each(function (MediaUsage $usage) use (&$released): void {
                $usable = $usage->usable;

                if ($usable instanceof Block) {
                    $settings = is_array($usable->settings_json) ? $usable->settings_json : [];
                    $field = (string) $usage->field;

                    if (is_array($settings['media'] ?? null)) {
                        unset($settings['media'][$field]);
                    }

                    if (is_array($settings['media_refs'] ?? null)) {
                        unset($settings['media_refs'][$field]);
                    }

                    $usable->settings_json = $settings;
                    $usable->save();
                }

                $usage->delete();
                $released++;
            });

        return $released;
    }

    protected function defaultLabel(Model $usable, string $field): string
    {
        $name = method_exists($usable, 'getAttribute') && filled($usable->getAttribute('title'))
            ? (string) $usable->getAttribute('title')
            : (method_exists($usable, 'getAttribute') && filled($usable->getAttribute('name'))
                ? (string) $usable->getAttribute('name')
                : class_basename($usable).' #'.$usable->getKey());

        return $name.' · '.$field;
    }
}
