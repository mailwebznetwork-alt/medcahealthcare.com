<?php

namespace App\Services\Media;

use App\Models\Media;

class MediaReferenceResolver
{
    /**
     * @param  array<string, mixed>  $mediaRefs  slot => media_id
     * @return array<string, string> slot => delivery path
     */
    public function pathsFromRefs(array $mediaRefs): array
    {
        $paths = [];
        foreach ($mediaRefs as $slot => $id) {
            $mediaId = is_numeric($id) ? (int) $id : null;
            if ($mediaId === null || $mediaId <= 0) {
                continue;
            }
            $media = Media::query()->find($mediaId);
            if ($media) {
                $paths[(string) $slot] = $media->referencePath();
            }
        }

        return $paths;
    }

    /**
     * @param  array<string, mixed>  $media
     * @param  array<string, mixed>  $mediaRefs
     * @return array<string, string>
     */
    public function mergeMediaPaths(array $media, array $mediaRefs): array
    {
        $resolved = $this->pathsFromRefs($mediaRefs);
        $merged = [];
        foreach ($media as $slot => $value) {
            if (is_string($value) && $value !== '') {
                $merged[(string) $slot] = $value;
            }
        }

        return array_replace($merged, $resolved);
    }

    public function findByPath(string $path): ?Media
    {
        $path = ltrim($path, '/');

        return Media::query()
            ->where('legacy_path', $path)
            ->orWhere('file_path', $path)
            ->orWhere('webp_path', $path)
            ->orWhere('optimized_path', $path)
            ->orWhere('large_path', $path)
            ->orWhere('small_path', $path)
            ->orWhere('medium_path', $path)
            ->orWhere('thumbnail_path', $path)
            ->first();
    }

    public function findByHash(string $hash): ?Media
    {
        return Media::query()->where('file_hash', $hash)->first();
    }

    public function resolveUrl(int|string|null $reference): ?string
    {
        if ($reference === null || $reference === '') {
            return null;
        }

        if (is_numeric($reference)) {
            $media = Media::query()->find((int) $reference);

            return $media ? $media->preferredImageUrl() : null;
        }

        $ref = (string) $reference;
        if (str_starts_with($ref, 'http://') || str_starts_with($ref, 'https://')) {
            return $ref;
        }

        $media = $this->findByPath($ref);

        return $media
            ? $media->preferredImageUrl()
            : MediaPublicUrl::forPath($ref);
    }
}
