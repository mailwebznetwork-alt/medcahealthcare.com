<?php

use App\Models\Media;
use App\Models\Service;
use App\Models\User;
use App\Services\Media\LegacyMediaMigrator;
use App\Services\Media\MediaReferenceResolver;
use App\Services\Media\MediaUploadProcessor;
use App\Services\Media\ServiceMediaAttacher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('public');
});

it('imports legacy disk files with hash deduplication', function (): void {
    Storage::disk('public')->put('services/1/photo.jpg', UploadedFile::fake()->image('photo.jpg', 400, 300)->getContent());

    $media = app(MediaUploadProcessor::class)->importFromDiskPath('services/1/photo.jpg', null, 'test');
    $dup = app(MediaUploadProcessor::class)->importFromDiskPath('services/1/photo.jpg', null, 'test');

    expect($media->id)->toBe($dup->id)
        ->and($media->file_hash)->not->toBeEmpty()
        ->and($media->legacy_path)->toBe('services/1/photo.jpg');
});

it('attaches service featured media by id from picker workflow', function (): void {
    $service = Service::factory()->create();
    $file = UploadedFile::fake()->image('pick.jpg', 300, 200);
    $media = app(MediaUploadProcessor::class)->process($file, null, 'test');

    app(ServiceMediaAttacher::class)->attachFeaturedById($service, $media->id);
    $service->refresh();

    expect($service->featured_media_id)->toBe($media->id)
        ->and(app(MediaReferenceResolver::class)->resolveUrl($media->id))->not->toBeEmpty();
});

it('resolves block media paths from media_refs', function (): void {
    $media = Media::query()->create([
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'file_name' => 'block.webp',
        'file_path' => 'media/test/original.webp',
        'webp_path' => 'media/test/full.webp',
        'file_type' => 'image',
    ]);

    $paths = app(MediaReferenceResolver::class)->pathsFromRefs(['image' => $media->id]);

    expect($paths['image'])->toBe('media/test/full.webp');
});

it('runs legacy migrator report structure', function (): void {
    Storage::disk('public')->put('services/2/icon.png', UploadedFile::fake()->image('icon.png')->getContent());

    $report = app(LegacyMediaMigrator::class)->migrate(['services']);

    expect($report)->toHaveKeys(['imported', 'skipped', 'duplicate', 'failed', 'errors'])
        ->and($report['imported'])->toBeGreaterThanOrEqual(0);
});

it('opens media picker modal for authenticated admin', function (): void {
    $user = User::factory()->create(['role' => 'super_admin']);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Media\MediaPickerModal::class)
        ->dispatch('open-media-picker', key: 'test-key', selectedId: null)
        ->assertSet('open', true)
        ->assertSet('activeKey', 'test-key');
});
