<?php

namespace App\Services\Media;

use App\Models\Block;
use App\Models\Media;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class LegacyMediaMigrator
{
    /**
     * @return array{imported: int, skipped: int, duplicate: int, failed: int, errors: list<string>}
     */
    public function migrate(array $scanDirs = []): array
    {
        $scanDirs = $scanDirs !== [] ? $scanDirs : config('media.legacy_scan_dirs', [
            'services',
            'deployment/block-media',
        ]);

        $report = ['imported' => 0, 'skipped' => 0, 'duplicate' => 0, 'failed' => 0, 'errors' => []];
        $processor = app(MediaUploadProcessor::class);
        $resolver = app(MediaReferenceResolver::class);
        $diskRoot = Storage::disk('public')->path('');

        foreach ($scanDirs as $dir) {
            $fullDir = $diskRoot.'/'.trim($dir, '/');
            if (! is_dir($fullDir)) {
                continue;
            }
            foreach (File::allFiles($fullDir) as $file) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($diskRoot) + 1));
                if ($this->shouldSkipPath($relative)) {
                    $report['skipped']++;

                    continue;
                }
                if ($resolver->findByPath($relative) || Media::query()->where('legacy_path', $relative)->exists()) {
                    $report['skipped']++;

                    continue;
                }
                try {
                    $hash = hash_file('sha256', $file->getPathname());
                    if ($resolver->findByHash($hash)) {
                        $report['duplicate']++;

                        continue;
                    }
                    $media = $processor->importFromDiskPath($relative, null, 'legacy-migration');
                    $this->updateReferences($relative, $media);
                    $report['imported']++;
                } catch (\Throwable $e) {
                    $report['failed']++;
                    $report['errors'][] = $relative.': '.$e->getMessage();
                }
            }
        }

        app(MediaUsagesIndexer::class)->reindexAll();

        return $report;
    }

    protected function shouldSkipPath(string $path): bool
    {
        if (str_starts_with($path, 'media/')) {
            return true;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return ! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true);
    }

    protected function updateReferences(string $legacyPath, Media $media): void
    {
        $refPath = $media->referencePath();

        Service::query()->where('featured_image', $legacyPath)->each(function (Service $s) use ($media, $refPath): void {
            $s->update(['featured_media_id' => $media->id, 'featured_image' => $refPath]);
        });
        Service::query()->where('icon', $legacyPath)->each(function (Service $s) use ($media, $refPath): void {
            $s->update(['icon_media_id' => $media->id, 'icon' => $refPath]);
        });
        Service::query()->whereNotNull('gallery')->each(function (Service $s) use ($legacyPath, $media, $refPath): void {
            $gallery = is_array($s->gallery) ? $s->gallery : [];
            $changed = false;
            foreach ($gallery as $i => $path) {
                if ((string) $path === $legacyPath) {
                    $gallery[$i] = $refPath;
                    $changed = true;
                }
            }
            if ($changed) {
                $ids = is_array($s->gallery_media_ids) ? $s->gallery_media_ids : [];
                $ids[] = $media->id;
                $s->update(['gallery' => $gallery, 'gallery_media_ids' => array_values(array_unique($ids))]);
            }
        });

        Block::query()->whereNotNull('settings_json')->each(function (Block $block) use ($legacyPath, $media, $refPath): void {
            $settings = is_array($block->settings_json) ? $block->settings_json : [];
            $mediaSlots = is_array($settings['media'] ?? null) ? $settings['media'] : [];
            $refs = is_array($settings['media_refs'] ?? null) ? $settings['media_refs'] : [];
            $changed = false;
            foreach ($mediaSlots as $slot => $path) {
                if ((string) $path === $legacyPath) {
                    $mediaSlots[$slot] = $refPath;
                    $refs[$slot] = $media->id;
                    $changed = true;
                }
            }
            if ($changed) {
                $settings['media'] = $mediaSlots;
                $settings['media_refs'] = $refs;
                $block->update(['settings_json' => $settings]);
            }
        });

        Page::query()->whereNotNull('block_overrides_json')->each(function (Page $page) use ($legacyPath, $media, $refPath): void {
            $overrides = is_array($page->block_overrides_json) ? $page->block_overrides_json : [];
            $changed = false;
            foreach ($overrides as $slug => $settings) {
                if (! is_array($settings)) {
                    continue;
                }
                $mediaSlots = is_array($settings['media'] ?? null) ? $settings['media'] : [];
                $refs = is_array($settings['media_refs'] ?? null) ? $settings['media_refs'] : [];
                foreach ($mediaSlots as $slot => $path) {
                    if ((string) $path === $legacyPath) {
                        $mediaSlots[$slot] = $refPath;
                        $refs[$slot] = $media->id;
                        $changed = true;
                    }
                }
                if ($changed) {
                    $settings['media'] = $mediaSlots;
                    $settings['media_refs'] = $refs;
                    $overrides[$slug] = $settings;
                }
            }
            if ($changed) {
                $page->update(['block_overrides_json' => $overrides]);
            }
        });
    }
}
