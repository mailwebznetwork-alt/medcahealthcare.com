<?php

namespace App\Services\Media;

use App\Models\Media;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CatalogMediaAttacher
{
    public function __construct(
        private readonly MediaUploadProcessor $processor,
        private readonly MediaUsageTracker $usageTracker,
        private readonly MediaImageSeoScorer $seoScorer,
        private readonly ServiceMediaAttacher $serviceMediaAttacher,
    ) {}

    public function syncFromRequest(Request $request, Service|ServiceCategory|SubService $entity, string $storageModule): void
    {
        if ($entity instanceof Service) {
            $this->syncServiceMedia($request, $entity);

            return;
        }

        $userId = $request->user()?->id;
        $prefix = rtrim($storageModule, '/').'/';

        $removeGallery = $request->input('remove_gallery', []);
        if (is_array($removeGallery) && $removeGallery !== []) {
            foreach ($removeGallery as $path) {
                if (! is_string($path) || $path === '') {
                    continue;
                }
                if (str_starts_with($path, $prefix)) {
                    $this->deletePublicPath($path);
                }
                $this->removeGalleryPath($entity, $path);
            }
        }

        if ($request->hasFile('featured_image')) {
            if (is_string($entity->featured_image) && str_starts_with($entity->featured_image, $prefix)) {
                $this->deletePublicPath($entity->featured_image);
            }
            $this->attachFeatured($entity, $request->file('featured_image'), $storageModule, $userId);
        } elseif ($request->filled('featured_media_id') && ! $request->hasFile('featured_image')) {
            $this->attachFeaturedById($entity, (int) $request->input('featured_media_id'));
        }

        if ($request->hasFile('icon')) {
            if (is_string($entity->icon) && str_starts_with($entity->icon, $prefix)) {
                $this->deletePublicPath($entity->icon);
            }
            $this->attachIcon($entity, $request->file('icon'), $storageModule, $userId);
        } elseif ($request->filled('icon_media_id') && ! $request->hasFile('icon')) {
            $this->attachIconById($entity, (int) $request->input('icon_media_id'));
        }

        $pickerGallery = $request->input('picker_gallery_media_ids', []);
        if (is_array($pickerGallery)) {
            foreach ($pickerGallery as $mediaId) {
                if (is_numeric($mediaId) && (int) $mediaId > 0) {
                    $this->attachGalleryById($entity, (int) $mediaId);
                }
            }
        }

        if ($request->hasFile('gallery_files')) {
            foreach ($request->file('gallery_files') as $file) {
                if ($file === null) {
                    continue;
                }
                $this->attachGalleryItem($entity, $file, $storageModule, $userId);
            }
        }
    }

    private function syncServiceMedia(Request $request, Service $service): void
    {
        $attacher = $this->serviceMediaAttacher;
        $userId = $request->user()?->id;

        $removeGallery = $request->input('remove_gallery', []);
        if (is_array($removeGallery) && $removeGallery !== []) {
            foreach ($removeGallery as $path) {
                if (! is_string($path) || $path === '') {
                    continue;
                }
                if (str_starts_with($path, 'services/')) {
                    $this->deletePublicPath($path);
                }
                $attacher->removeGalleryPath($service, $path);
            }
        }

        if ($request->hasFile('featured_image')) {
            if (is_string($service->featured_image) && str_starts_with($service->featured_image, 'services/')) {
                $this->deletePublicPath($service->featured_image);
            }
            $attacher->attachFeatured($service, $request->file('featured_image'), $userId);
        } elseif ($request->filled('featured_media_id') && ! $request->hasFile('featured_image')) {
            $attacher->attachFeaturedById($service, (int) $request->input('featured_media_id'));
        }

        if ($request->hasFile('icon')) {
            if (is_string($service->icon) && str_starts_with($service->icon, 'services/')) {
                $this->deletePublicPath($service->icon);
            }
            $attacher->attachIcon($service, $request->file('icon'), $userId);
        } elseif ($request->filled('icon_media_id') && ! $request->hasFile('icon')) {
            $attacher->attachIconById($service, (int) $request->input('icon_media_id'));
        }

        $pickerGallery = $request->input('picker_gallery_media_ids', []);
        if (is_array($pickerGallery)) {
            foreach ($pickerGallery as $mediaId) {
                if (is_numeric($mediaId) && (int) $mediaId > 0) {
                    $attacher->attachGalleryById($service, (int) $mediaId);
                }
            }
        }

        if ($request->hasFile('gallery_files')) {
            foreach ($request->file('gallery_files') as $file) {
                if ($file === null) {
                    continue;
                }
                $attacher->attachGalleryItem($service, $file, $userId);
            }
        }
    }

    public function attachFeaturedById(ServiceCategory|SubService $entity, int $mediaId): ?Media
    {
        $media = Media::query()->find($mediaId);
        if ($media === null) {
            return null;
        }

        $this->releaseFeatured($entity);
        $this->applyFeaturedMeta($entity, $media);
        $this->usageTracker->attach($media, $entity, 'featured_image', $entity->title.' · Featured');
        $entity->featured_media_id = $media->id;
        $entity->featured_image = $media->referencePath();

        return $media;
    }

    public function attachIconById(ServiceCategory|SubService $entity, int $mediaId): ?Media
    {
        $media = Media::query()->find($mediaId);
        if ($media === null) {
            return null;
        }

        $this->releaseIcon($entity);
        $this->usageTracker->attach($media, $entity, 'icon', $entity->title.' · Icon');
        $entity->icon_media_id = $media->id;
        $entity->icon = $media->referencePath();

        return $media;
    }

    public function attachFeatured(ServiceCategory|SubService $entity, UploadedFile $file, string $module, ?int $userId = null): Media
    {
        $this->releaseFeatured($entity);
        $media = $this->processor->process($file, $userId, $module);
        $this->applyFeaturedMeta($entity, $media);
        $this->usageTracker->attach($media, $entity, 'featured_image', $entity->title.' · Featured');
        $entity->featured_media_id = $media->id;
        $entity->featured_image = $media->referencePath();

        return $media;
    }

    public function attachIcon(ServiceCategory|SubService $entity, UploadedFile $file, string $module, ?int $userId = null): Media
    {
        $this->releaseIcon($entity);
        $media = $this->processor->process($file, $userId, $module);
        $this->usageTracker->attach($media, $entity, 'icon', $entity->title.' · Icon');
        $entity->icon_media_id = $media->id;
        $entity->icon = $media->referencePath();

        return $media;
    }

    public function attachGalleryById(ServiceCategory|SubService $entity, int $mediaId): ?Media
    {
        $media = Media::query()->find($mediaId);
        if ($media === null) {
            return null;
        }

        $ids = is_array($entity->gallery_media_ids) ? $entity->gallery_media_ids : [];
        if (in_array($media->id, $ids, true)) {
            return $media;
        }

        $gallery = is_array($entity->gallery) ? $entity->gallery : [];
        $gallery[] = $media->referencePath();
        $ids[] = $media->id;
        $entity->gallery = $gallery;
        $entity->gallery_media_ids = $ids;
        $this->usageTracker->attach($media, $entity, 'gallery:'.$media->id, $entity->title.' · Gallery');

        return $media;
    }

    public function attachGalleryItem(ServiceCategory|SubService $entity, UploadedFile $file, string $module, ?int $userId = null): Media
    {
        $media = $this->processor->process($file, $userId, $module);
        $gallery = is_array($entity->gallery) ? $entity->gallery : [];
        $gallery[] = $media->referencePath();
        $entity->gallery = $gallery;

        $ids = is_array($entity->gallery_media_ids) ? $entity->gallery_media_ids : [];
        $ids[] = $media->id;
        $entity->gallery_media_ids = $ids;
        $this->usageTracker->attach($media, $entity, 'gallery:'.$media->id, $entity->title.' · Gallery');

        return $media;
    }

    public function removeGalleryPath(ServiceCategory|SubService $entity, string $path): void
    {
        $gallery = is_array($entity->gallery) ? $entity->gallery : [];
        $ids = is_array($entity->gallery_media_ids) ? $entity->gallery_media_ids : [];
        $newGallery = [];
        $newIds = [];

        foreach ($gallery as $i => $item) {
            if ((string) $item === $path) {
                $mediaId = $ids[$i] ?? null;
                if ($mediaId) {
                    $media = Media::query()->find((int) $mediaId);
                    if ($media !== null) {
                        $this->usageTracker->detach($media, $entity, 'gallery:'.$media->id);
                    }
                }

                continue;
            }
            $newGallery[] = $item;
            if (isset($ids[$i])) {
                $newIds[] = $ids[$i];
            }
        }

        $entity->gallery = $newGallery === [] ? null : $newGallery;
        $entity->gallery_media_ids = $newIds === [] ? null : $newIds;
    }

    public function releaseFeatured(ServiceCategory|SubService $entity): void
    {
        if ($entity->featured_media_id !== null) {
            $media = Media::query()->find($entity->featured_media_id);
            if ($media !== null) {
                $this->usageTracker->detach($media, $entity, 'featured_image');
            }
        }
    }

    public function releaseIcon(ServiceCategory|SubService $entity): void
    {
        if ($entity->icon_media_id !== null) {
            $media = Media::query()->find($entity->icon_media_id);
            if ($media !== null) {
                $this->usageTracker->detach($media, $entity, 'icon');
            }
        }
    }

    protected function applyFeaturedMeta(ServiceCategory|SubService $entity, Media $media): void
    {
        $meta = is_array($entity->featured_image_meta) ? $entity->featured_image_meta : [];
        if (filled($meta['alt'] ?? $entity->image_alt)) {
            $media->alt_text = (string) ($meta['alt'] ?? $entity->image_alt);
        }
        if (filled($meta['title'] ?? null)) {
            $media->title = (string) $meta['title'];
        }
        if (filled($meta['caption'] ?? null)) {
            $media->caption = (string) $meta['caption'];
        }
        if (filled($meta['description'] ?? null)) {
            $media->description = (string) $meta['description'];
        }
        $this->seoScorer->persist($media);
    }

    private function deletePublicPath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
