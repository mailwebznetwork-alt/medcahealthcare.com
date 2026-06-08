<?php

namespace App\Services\Bulk;

use App\Models\Block;
use App\Models\Blog;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\ActivityLogService;
use App\Services\Operations\ServiceLifecycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class BulkDuplicateService
{
    public function __construct(
        private readonly ServiceLifecycle $serviceLifecycle,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function duplicate(string $resourceKey, mixed $model, User $user): bool
    {
        return match ($resourceKey) {
            'operations.services' => $user->can('create', Service::class) && $this->duplicateService($model),
            'operations.vacancies' => $user->can('create', Vacancy::class) && $this->duplicateVacancy($model),
            'operations.service_categories' => $user->can('create', ServiceCategory::class) && $this->duplicateCategory($model),
            'site_architect.pages' => $user->can('create', Page::class) && $this->duplicatePage($model),
            'site_architect.blogs' => $user->can('create', Blog::class) && $this->duplicateBlog($model),
            'site_architect.blocks' => $user->can('create', Block::class) && $this->duplicateBlock($model),
            default => false,
        };
    }

    private function duplicateService(Service $service): bool
    {
        $this->serviceLifecycle->duplicate($service);

        return true;
    }

    private function duplicateVacancy(Vacancy $vacancy): bool
    {
        $vacancy->duplicateAsDraft();

        return true;
    }

    private function duplicateCategory(ServiceCategory $category): bool
    {
        DB::transaction(function () use ($category): void {
            $copy = $category->replicate();
            $baseCode = $category->code.'_copy';
            $copy->code = $baseCode;
            $n = 1;
            while (ServiceCategory::query()->where('code', $copy->code)->exists()) {
                $copy->code = $baseCode.'_'.$n;
                $n++;
            }
            $copy->name = $category->name.' ('.__('Copy').')';
            $copy->slug = null;
            $copy->is_active = false;
            $copy->save();
        });

        return true;
    }

    private function duplicatePage(Page $original): bool
    {
        $original->loadMissing('pinCodes');

        DB::transaction(function () use ($original): void {
            $new = $original->replicate();
            $new->uuid = (string) Str::uuid();
            $new->title = $original->title.' ('.__('Copy').')';
            $baseSlug = $original->slug.'-copy';
            $new->slug = $baseSlug;
            $n = 1;
            while (Page::query()->where('slug', $new->slug)->exists()) {
                $new->slug = $baseSlug.'-'.$n;
                $n++;
            }
            $new->is_active = false;
            $new->save();

            $sync = [];
            foreach ($original->pinCodes as $pc) {
                $sync[$pc->id] = [
                    'serviceability' => (bool) $pc->pivot->serviceability,
                    'delivery_charge' => $pc->pivot->delivery_charge,
                    'location_keywords' => $pc->pivot->location_keywords,
                ];
            }
            $new->pinCodes()->sync($sync);
        });

        $this->activityLog->log('page_duplicate', 'site_architect', 'Bulk duplicate from page '.$original->id);

        return true;
    }

    private function duplicateBlog(Blog $original): bool
    {
        DB::transaction(function () use ($original): void {
            $new = $original->replicate();
            $new->forceFill(['uuid' => (string) Str::uuid()]);
            $new->title = $original->title.' ('.__('Copy').')';
            $baseSlug = $original->slug.'-copy';
            $new->slug = $baseSlug;
            $n = 1;
            while (Blog::query()->where('slug', $new->slug)->exists()) {
                $new->slug = $baseSlug.'-'.$n;
                $n++;
            }
            $new->is_published = false;
            $new->published_at = null;

            if ($original->featured_image && ! Str::startsWith($original->featured_image, ['http://', 'https://'])) {
                if (Storage::disk('public')->exists($original->featured_image)) {
                    $ext = pathinfo($original->featured_image, PATHINFO_EXTENSION);
                    $newPath = 'blogs/'.Str::uuid().($ext !== '' ? '.'.$ext : '');
                    Storage::disk('public')->copy($original->featured_image, $newPath);
                    $new->featured_image = $newPath;
                }
            }

            $new->save();
        });

        return true;
    }

    private function duplicateBlock(Block $original): bool
    {
        if ($original->is_managed) {
            return false;
        }

        DB::transaction(function () use ($original): void {
            $new = $original->replicate();
            $baseSlug = $original->block_slug.'-copy';
            $slug = $baseSlug;
            $n = 1;
            while (Block::query()->where('block_slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$n;
                $n++;
            }

            $new->forceFill([
                'uuid' => (string) Str::uuid(),
                'block_name' => $original->block_name.' ('.__('Copy').')',
                'block_slug' => $slug,
                'is_active' => false,
                'is_managed' => false,
            ]);
            $new->save();
        });

        return true;
    }
}
