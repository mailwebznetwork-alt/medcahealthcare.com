<?php

namespace App\Services\Media;

use App\Models\Block;
use App\Models\Media;
use App\Models\Page;
use App\Models\Service;

class MediaUsagesIndexer
{
    public function __construct(
        private readonly MediaUsageTracker $tracker,
    ) {}

    public function reindexAll(): int
    {
        \App\Models\MediaUsage::query()->delete();

        $count = 0;
        $count += $this->indexServices();
        $count += $this->indexBlocks();
        $count += $this->indexPages();

        return $count;
    }

    public function indexServices(): int
    {
        $count = 0;
        Service::query()->orderBy('id')->each(function (Service $service) use (&$count): void {
            if ($service->featured_media_id) {
                $media = Media::query()->find($service->featured_media_id);
                if ($media) {
                    $this->tracker->attach($media, $service, 'featured_image', $service->title.' · Featured');
                    $count++;
                }
            } elseif (filled($service->featured_image)) {
                $media = $this->findByPath((string) $service->featured_image);
                if ($media) {
                    $service->featured_media_id = $media->id;
                    $service->saveQuietly();
                    $this->tracker->attach($media, $service, 'featured_image', $service->title.' · Featured');
                    $count++;
                }
            }

            if ($service->icon_media_id) {
                $media = Media::query()->find($service->icon_media_id);
                if ($media) {
                    $this->tracker->attach($media, $service, 'icon', $service->title.' · Icon');
                    $count++;
                }
            } elseif (filled($service->icon)) {
                $media = $this->findByPath((string) $service->icon);
                if ($media) {
                    $service->icon_media_id = $media->id;
                    $service->saveQuietly();
                    $this->tracker->attach($media, $service, 'icon', $service->title.' · Icon');
                    $count++;
                }
            }

            $ids = is_array($service->gallery_media_ids) ? $service->gallery_media_ids : [];
            $gallery = is_array($service->gallery) ? $service->gallery : [];
            foreach ($gallery as $i => $path) {
                $mediaId = $ids[$i] ?? null;
                $media = $mediaId ? Media::query()->find($mediaId) : $this->findByPath((string) $path);
                if ($media) {
                    $this->tracker->attach($media, $service, 'gallery:'.$media->id, $service->title.' · Gallery');
                    $count++;
                }
            }
        });

        return $count;
    }

    public function indexBlocks(): int
    {
        $count = 0;
        Block::query()->orderBy('id')->each(function (Block $block) use (&$count): void {
            $settings = is_array($block->settings_json) ? $block->settings_json : [];
            $refs = is_array($settings['media_refs'] ?? null) ? $settings['media_refs'] : [];
            foreach ($refs as $slot => $mediaId) {
                if (! is_numeric($mediaId)) {
                    continue;
                }
                $asset = Media::query()->find((int) $mediaId);
                if ($asset) {
                    $this->tracker->attach($asset, $block, (string) $slot, $block->block_slug.' · '.$slot);
                    $count++;
                }
            }
            $media = is_array($settings['media'] ?? null) ? $settings['media'] : [];
            foreach ($media as $slot => $path) {
                if (! is_string($path) || $path === '' || isset($refs[$slot])) {
                    continue;
                }
                $asset = $this->findByPath($path);
                if ($asset) {
                    $this->tracker->attach($asset, $block, (string) $slot, $block->block_slug.' · '.$slot);
                    $count++;
                }
            }
        });

        return $count;
    }

    public function indexPages(): int
    {
        $count = 0;
        Page::query()->orderBy('id')->each(function (Page $page) use (&$count): void {
            $overrides = is_array($page->block_overrides_json) ? $page->block_overrides_json : [];
            foreach ($overrides as $slug => $settings) {
                if (! is_array($settings)) {
                    continue;
                }
                $refs = is_array($settings['media_refs'] ?? null) ? $settings['media_refs'] : [];
                foreach ($refs as $slot => $mediaId) {
                    if (! is_numeric($mediaId)) {
                        continue;
                    }
                    $asset = Media::query()->find((int) $mediaId);
                    if ($asset) {
                        $this->tracker->attach($asset, $page, $slug.'.'.$slot, $page->title.' · '.$slug);
                        $count++;
                    }
                }
                $media = is_array($settings['media'] ?? null) ? $settings['media'] : [];
                foreach ($media as $slot => $path) {
                    if (! is_string($path) || $path === '' || isset($refs[$slot])) {
                        continue;
                    }
                    $asset = $this->findByPath($path);
                    if ($asset) {
                        $this->tracker->attach($asset, $page, $slug.'.'.$slot, $page->title.' · '.$slug);
                        $count++;
                    }
                }
            }
        });

        return $count;
    }

    protected function findByPath(string $path): ?Media
    {
        $path = ltrim($path, '/');

        return Media::query()
            ->where('file_path', $path)
            ->orWhere('webp_path', $path)
            ->orWhere('optimized_path', $path)
            ->orWhere('large_path', $path)
            ->orWhere('small_path', $path)
            ->orWhere('medium_path', $path)
            ->orWhere('thumbnail_path', $path)
            ->first();
    }
}
