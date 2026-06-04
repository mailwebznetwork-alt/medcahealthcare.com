<?php

namespace App\Repositories\Operations;

use App\Models\ServiceCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ServiceCategoryRepository
{
    /**
     * @return LengthAwarePaginator<int, ServiceCategory>
     */
    public function paginateFiltered(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'active' => ['nullable', 'in:0,1'],
            'parent_id' => ['nullable'],
        ]);

        $query = ServiceCategory::query()
            ->with(['parent:id,name,code'])
            ->withCount('services')
            ->ordered();

        if (! empty($validated['q'])) {
            $term = $validated['q'];
            $query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('code', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%');
            });
        }

        if (isset($validated['active']) && $validated['active'] !== '') {
            $query->where('is_active', $validated['active'] === '1');
        }

        if (isset($validated['parent_id']) && $validated['parent_id'] !== '') {
            if ($validated['parent_id'] === '0') {
                $query->whereNull('parent_id');
            } elseif (is_numeric($validated['parent_id'])) {
                $query->where('parent_id', (int) $validated['parent_id']);
            }
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Active categories for multi-selects (admin + filters).
     *
     * @return Collection<int, ServiceCategory>
     */
    public function activeForPicker(): Collection
    {
        return ServiceCategory::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'code', 'parent_id', 'sort_order']);
    }

    /**
     * @return Collection<int, ServiceCategory>
     */
    public function parentOptions(?int $exceptId = null): Collection
    {
        return ServiceCategory::query()
            ->ordered()
            ->when($exceptId !== null, fn (Builder $q) => $q->where('id', '!=', $exceptId))
            ->get(['id', 'name', 'code', 'parent_id']);
    }
}
