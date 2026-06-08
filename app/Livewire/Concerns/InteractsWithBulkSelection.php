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
        if (in_array($id, $this->bulkSelectedIds, true)) {
            $this->bulkSelectedIds = array_values(array_diff($this->bulkSelectedIds, [$id]));
        } else {
            $this->bulkSelectedIds[] = $id;
        }

        $this->bulkSelectAllFiltered = false;
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

    public function selectAllFilteredRows(): void
    {
        $this->bulkSelectedIds = $this->bulkFilteredIdsQuery()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->bulkSelectAllFiltered = true;
    }

    public function deselectAllRows(): void
    {
        $this->bulkSelectedIds = [];
        $this->bulkSelectAllFiltered = false;
    }

    public function bulkSelectedCount(): int
    {
        return count($this->bulkSelectedIds);
    }

    public function isBulkRowSelected(int $id): bool
    {
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
