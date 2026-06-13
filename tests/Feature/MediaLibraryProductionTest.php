<?php

use App\Models\Media;
use App\Models\Service;
use App\Services\Media\MediaImageSeoScorer;
use App\Services\Media\MediaUploadProcessor;
use App\Services\Media\MediaUsageTracker;
use App\Services\Media\ServiceMediaAttacher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

it('processes uploads into the media library with webp variants', function (): void {
    $file = UploadedFile::fake()->image('nurse.jpg', 800, 600);

    $media = app(MediaUploadProcessor::class)->process($file, null, 'test');

    expect($media->uuid)->not->toBeEmpty()
        ->and($media->file_path)->toContain('media/')
        ->and($media->file_type)->toBe('image')
        ->and($media->width)->toBeGreaterThan(0)
        ->and($media->image_seo_score)->not->toBeNull();

    Storage::disk('public')->assertExists($media->file_path);
});

it('attaches service featured image via media library without duplicating storage paths', function (): void {
    $service = Service::factory()->create();
    $file = UploadedFile::fake()->image('featured.jpg', 640, 480);

    app(ServiceMediaAttacher::class)->attachFeatured($service, $file);
    $service->refresh();

    expect($service->featured_media_id)->not->toBeNull()
        ->and($service->featured_image)->toContain('media/')
        ->and(app(MediaUsageTracker::class)->isInUse(Media::query()->findOrFail($service->featured_media_id)))->toBeTrue();
});

it('scores image seo when alt text is present', function (): void {
    $media = Media::query()->create([
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'file_name' => 'test.webp',
        'file_path' => 'media/test/original.webp',
        'file_type' => 'image',
        'alt_text' => 'Home nursing in Bangalore',
        'title' => 'Nursing care',
    ]);

    $score = app(MediaImageSeoScorer::class)->score($media);

    expect($score)->toBeGreaterThan(40);
});

it('blocks media library delete when asset is in use', function (): void {
    $service = Service::factory()->create();
    $file = UploadedFile::fake()->image('icon.png', 64, 64);
    $media = app(ServiceMediaAttacher::class)->attachIcon($service, $file);

    expect(app(MediaUsageTracker::class)->isInUse($media))->toBeTrue();
});

it('releases block references when force deleting in-use media', function (): void {
    Storage::fake('public');

    $block = \App\Models\Block::query()->create([
        'block_slug' => 'hero-home',
        'block_name' => 'Hero Home',
        'block_type' => 'Hero',
        'code' => '@include("blocks.home.hero-home")',
        'is_active' => true,
        'settings_json' => [],
    ]);

    $file = UploadedFile::fake()->image('hero.jpg', 800, 600);
    $media = app(\App\Services\Media\MediaUploadProcessor::class)->process($file, null, 'test');
    app(MediaUsageTracker::class)->attach($media, $block, 'desktop_image', 'hero-home · desktop_image');

    $block->update([
        'settings_json' => [
            'media' => ['desktop_image' => $media->referencePath()],
            'media_refs' => ['desktop_image' => $media->id],
        ],
    ]);

    $released = app(MediaUsageTracker::class)->releaseAllReferencesFor($media);

    expect($released)->toBe(1)
        ->and(app(MediaUsageTracker::class)->isInUse($media))->toBeFalse()
        ->and($block->fresh()->settings_json['media']['desktop_image'] ?? 'missing')->toBe('missing');
});
