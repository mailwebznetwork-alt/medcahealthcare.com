<?php

namespace App\Services\Operations;

use App\Models\ServiceCategory;
use App\Services\ActivityLogService;
use App\Services\Governance\AdminDeletionGuard;
use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Governance\MasterDataAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceCategoryService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly AdminDeletionGuard $deletionGuard,
        private readonly DownstreamArtifactPurger $purger,
        private readonly MasterDataAudit $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ServiceCategory
    {
        return DB::transaction(function () use ($data): ServiceCategory {
            $category = ServiceCategory::query()->create($this->payloadFromInput($data));

            $this->activityLog->log('service_category.created', 'operations', $category->name.' ('.$category->code.')');

            return $category;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ServiceCategory $category, array $data): ServiceCategory
    {
        return DB::transaction(function () use ($category, $data): ServiceCategory {
            $parentId = $this->normalizeParentId($data['parent_id'] ?? null);
            $this->assertValidParent($category, $parentId);

            $category->update($this->payloadFromInput($data, $category));

            $this->activityLog->log('service_category.updated', 'operations', $category->name.' ('.$category->code.')');

            return $category->fresh();
        });
    }

    public function delete(ServiceCategory $category): void
    {
        DB::transaction(function () use ($category): void {
            $category->services()->detach();
            $category->children()->update(['parent_id' => $category->parent_id]);
            $this->deletionGuard->recordCategoryDeletion($category, 'ui');
            $category->delete();
            $this->audit->categoryDeleted($category, 'ui');
            $this->activityLog->log('service_category.deleted', 'operations', $category->name.' ('.$category->code.')');
        });

        $this->purger->purgeAfterCatalogEntityChange();
    }

    /**
     * @param  list<int|string>  $categoryIds
     */
    public function syncServiceCategories(\App\Models\Service $service, array $categoryIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): int => (int) $id,
            $categoryIds
        ), static fn (int $id): bool => $id > 0)));

        $service->categories()->sync($ids);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payloadFromInput(array $data, ?ServiceCategory $existing = null): array
    {
        $code = ServiceCategory::normalizeCode((string) ($data['code'] ?? ''));
        if ($code === '' && $existing === null) {
            $code = ServiceCategory::normalizeCode((string) ($data['name'] ?? ''));
        }

        $payload = [
            'name' => trim((string) ($data['name'] ?? '')),
            'code' => $code,
            'slug' => isset($data['slug']) ? ServiceCategory::normalizeCode((string) $data['slug']) : null,
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'parent_id' => $this->normalizeParentId($data['parent_id'] ?? null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'visibility' => (string) ($data['visibility'] ?? 'public'),
            'show_on_homepage' => (bool) ($data['show_on_homepage'] ?? false),
            'show_on_about' => (bool) ($data['show_on_about'] ?? false),
            'show_on_contact' => (bool) ($data['show_on_contact'] ?? false),
            'page_id' => filled($data['page_id'] ?? null) ? (int) $data['page_id'] : null,
        ];

        if ($payload['description'] === '') {
            $payload['description'] = null;
        }

        if ($existing !== null && $payload['code'] === '') {
            $payload['code'] = $existing->code;
        }

        return $payload;
    }

    private function normalizeParentId(mixed $parentId): ?int
    {
        if ($parentId === null || $parentId === '' || $parentId === '0' || $parentId === 0) {
            return null;
        }

        return (int) $parentId;
    }

    private function assertValidParent(ServiceCategory $category, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($parentId === (int) $category->id) {
            throw ValidationException::withMessages([
                'parent_id' => [__('A category cannot be its own parent.')],
            ]);
        }

        $ancestorId = $parentId;
        $guard = 0;
        while ($ancestorId !== null && $guard < 32) {
            if ($ancestorId === (int) $category->id) {
                throw ValidationException::withMessages([
                    'parent_id' => [__('Invalid parent — would create a circular hierarchy.')],
                ]);
            }

            $ancestor = ServiceCategory::query()->find($ancestorId);
            $ancestorId = $ancestor?->parent_id;
            $guard++;
        }
    }
}
