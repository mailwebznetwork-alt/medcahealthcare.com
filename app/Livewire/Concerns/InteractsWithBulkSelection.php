<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Universal row selection for paginated Livewire list screens.
 */
trait InteractsWithBulkSelection
{
    /** @var list<int> */
    public array $bulkSelectedIds = [];

    public bool $bulkSelectAllFiltered = false;

    public function bulkResourceKey(): string
    {
        return 'generic';
    }

    public function toggleBulkRow(int $id): void
    {
        if ($this->bulkSelectAllFiltered) {
            $this->bulkSelectedIds = $this->bulkFilteredIdsQuery()
                ->whereKeyNot($id)
                ->pluck('id')
                ->map(fn ($rowId) => (int) $rowId)
                ->all();
            $this->bulkSelectAllFiltered = false;

            return;
        }

        if (in_array($id, $this->bulkSelectedIds, true)) {
            $this->bulkSelectedIds = array_values(array_diff($this->bulkSelectedIds, [$id]));
        } else {
            $this->bulkSelectedIds[] = $id;
        }
    }

    /**
     * Select every row in the current list (all pages), respecting active filters.
     */
    public function selectAllRows(): void
    {
        $this->bulkSelectedIds = [];
        $this->bulkSelectAllFiltered = true;
    }

    /**
     * @param  list<int>  $visibleIds
     */
    public function selectAllVisibleRows(array $visibleIds): void
    {
        $this->bulkSelectedIds = array_values(array_unique(array_merge(
            $this->bulkSelectedIds,
            array_map('intval', $visibleIds),
        )));
        $this->bulkSelectAllFiltered = false;
    }

    /** @deprecated Use selectAllRows() */
    public function selectAllFilteredRows(): void
    {
        $this->selectAllRows();
    }

    public function deselectAllRows(): void
    {
        $this->bulkSelectedIds = [];
        $this->bulkSelectAllFiltered = false;
    }

    public function bulkTotalSelectableCount(): int
    {
        return $this->bulkFilteredIdsQuery()->count();
    }

    public function bulkSelectedCount(): int
    {
        if ($this->bulkSelectAllFiltered) {
            return $this->bulkTotalSelectableCount();
        }

        return count($this->bulkSelectedIds);
    }

    public function isBulkRowSelected(int $id): bool
    {
        if ($this->bulkSelectAllFiltered) {
            return $this->bulkFilteredIdsQuery()->whereKey($id)->exists();
        }

        return in_array($id, $this->bulkSelectedIds, true);
    }

    /**
     * @return list<int>
     */
    public function resolvedBulkSelectedIds(): array
    {
        if ($this->bulkSelectAllFiltered) {
            return $this->bulkFilteredIdsQuery()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return $this->bulkSelectedIds;
    }

    /**
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    abstract protected function bulkFilteredIdsQuery(): Builder;
}
