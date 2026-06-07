<?php

namespace App\Services\Media;

use App\Models\Media;
use App\Models\Service;
use Illuminate\Http\UploadedFile;

class ServiceMediaAttacher
{
    public function __construct(
        private readonly MediaUploadProcessor $processor,
        private readonly MediaUsageTracker $usageTracker,
        private readonly MediaImageSeoScorer $seoScorer,
    ) {}

    public function attachFeaturedById(Service $service, int $mediaId): ?Media
    {
        $media = Media::query()->find($mediaId);
        if ($media === null) {
            return null;
        }
        $this->releaseFeatured($service);
        $this->applyServiceFeaturedMeta($service, $media);
        $this->usageTracker->attach($media, $service, 'featured_image', $service->title.' · Featured');
        $service->featured_media_id = $media->id;
        $service->featured_image = $media->referencePath();
        $service->save();

        return $media;
    }

    public function attachIconById(Service $service, int $mediaId): ?Media
    {
        $media = Media::query()->find($mediaId);
        if ($media === null) {
            return null;
        }
        $this->releaseIcon($service);
        $this->usageTracker->attach($media, $service, 'icon', $service->title.' · Icon');
        $service->icon_media_id = $media->id;
        $service->icon = $media->referencePath();
        $service->save();

        return $media;
    }

    public function attachGalleryById(Service $service, int $mediaId): ?Media
    {
        $media = Media::query()->find($mediaId);
        if ($media === null) {
            return null;
        }
        $ids = is_array($service->gallery_media_ids) ? $service->gallery_media_ids : [];
        if (in_array($media->id, $ids, true)) {
            return $media;
        }
        $gallery = is_array($service->gallery) ? $service->gallery : [];
        $gallery[] = $media->referencePath();
        $ids[] = $media->id;
        $service->gallery = $gallery;
        $service->gallery_media_ids = $ids;
        $service->save();
        $this->usageTracker->attach($media, $service, 'gallery:'.$media->id, $service->title.' · Gallery');

        return $media;
    }

    public function attachFeatured(Service $service, UploadedFile $file, ?int $userId = null, ?string $sourceModule = 'services'): Media
    {
        $this->releaseFeatured($service);

        $media = $this->processor->process($file, $userId, $sourceModule);
        $this->applyServiceFeaturedMeta($service, $media);
        $this->usageTracker->attach($media, $service, 'featured_image', $service->title.' · Featured');
        $service->featured_media_id = $media->id;
        $service->featured_image = $media->referencePath();
        $service->save();

        return $media;
    }

    public function attachIcon(Service $service, UploadedFile $file, ?int $userId = null): Media
    {
        $this->releaseIcon($service);

        $media = $this->processor->process($file, $userId, 'services');
        $this->usageTracker->attach($media, $service, 'icon', $service->title.' · Icon');
        $service->icon_media_id = $media->id;
        $service->icon = $media->referencePath();
        $service->save();

        return $media;
    }

    public function attachGalleryItem(Service $service, UploadedFile $file, ?int $userId = null): Media
    {
        $media = $this->processor->process($file, $userId, 'services');
        $path = $media->referencePath();
        $gallery = is_array($service->gallery) ? $service->gallery : [];
        $gallery[] = $path;
        $service->gallery = $gallery;

        $ids = is_array($service->gallery_media_ids) ? $service->gallery_media_ids : [];
        $ids[] = $media->id;
        $service->gallery_media_ids = $ids;
        $service->save();

        $this->usageTracker->attach($media, $service, 'gallery:'.$media->id, $service->title.' · Gallery');

        return $media;
    }

    public function removeGalleryPath(Service $service, string $path): void
    {
        $gallery = is_array($service->gallery) ? $service->gallery : [];
        $ids = is_array($service->gallery_media_ids) ? $service->gallery_media_ids : [];
        $newGallery = [];
        $newIds = [];

        foreach ($gallery as $i => $item) {
            if ((string) $item === $path) {
                $mediaId = $ids[$i] ?? null;
                if ($mediaId) {
                    $media = Media::query()->find((int) $mediaId);
                    if ($media !== null) {
                        $this->usageTracker->detach($media, $service, 'gallery:'.$media->id);
                    }
                }

                continue;
            }
            $newGallery[] = $item;
            if (isset($ids[$i])) {
                $newIds[] = $ids[$i];
            }
        }

        $service->gallery = $newGallery === [] ? null : $newGallery;
        $service->gallery_media_ids = $newIds === [] ? null : $newIds;
        $service->save();
    }

    public function releaseFeatured(Service $service): void
    {
        if ($service->featured_media_id !== null) {
            $media = Media::query()->find($service->featured_media_id);
            if ($media !== null) {
                $this->usageTracker->detach($media, $service, 'featured_image');
            }
        }
    }

    public function releaseIcon(Service $service): void
    {
        if ($service->icon_media_id !== null) {
            $media = Media::query()->find($service->icon_media_id);
            if ($media !== null) {
                $this->usageTracker->detach($media, $service, 'icon');
            }
        }
    }

    protected function applyServiceFeaturedMeta(Service $service, Media $media): void
    {
        $meta = is_array($service->featured_image_meta) ? $service->featured_image_meta : [];
        if (filled($meta['alt'] ?? $service->image_alt)) {
            $media->alt_text = (string) ($meta['alt'] ?? $service->image_alt);
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
        $media->category = 'service-featured';
        $this->seoScorer->persist($media);
    }
}
