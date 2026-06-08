<?php

namespace App\Livewire\Concerns;

use App\Services\Bulk\BulkActionService;

trait InteractsWithBulkActions
{
    use InteractsWithBulkSelection;

    public bool $bulkModalOpen = false;

    public string $bulkPendingAction = '';

    public string $bulkDeleteConfirmText = '';

    /** @var array<string, mixed> */
    public array $bulkGovernancePreview = [];

    public function openBulkAction(string $action): void
    {
        $ids = $this->resolvedBulkSelectedIds();
        if ($ids === []) {
            session()->flash('error', __('Select at least one row.'));

            return;
        }

        $this->bulkPendingAction = $action;
        $this->bulkGovernancePreview = app(BulkActionService::class)->governancePreview(
            $this->bulkResourceKey(),
            $ids,
            $action,
        );
        $this->bulkDeleteConfirmText = '';
        $this->bulkModalOpen = true;
    }

    public function cancelBulkAction(): void
    {
        $this->bulkModalOpen = false;
        $this->bulkPendingAction = '';
        $this->bulkGovernancePreview = [];
        $this->bulkDeleteConfirmText = '';
    }

    public function confirmBulkAction(): void
    {
        $ids = $this->resolvedBulkSelectedIds();
        if ($ids === [] || $this->bulkPendingAction === '') {
            $this->cancelBulkAction();

            return;
        }

        $destructive = ($this->bulkGovernancePreview['requires_delete_confirmation'] ?? false) === true;
        if ($destructive && strtoupper(trim($this->bulkDeleteConfirmText)) !== 'DELETE') {
            $this->addError('bulkDeleteConfirmText', __('Type DELETE to confirm this irreversible action.'));

            return;
        }

        $service = app(BulkActionService::class);

        if ($this->bulkPendingAction === 'export') {
            $this->cancelBulkAction();
            $this->redirect(route('site-architect.bulk.export', [
                'resource' => $this->bulkResourceKey(),
                'ids' => implode(',', $ids),
                'format' => 'json',
            ]));

            return;
        }

        $result = $service->execute(
            $this->bulkResourceKey(),
            $ids,
            $this->bulkPendingAction,
            auth()->user(),
        );

        session()->flash('status', $result['message']);
        $this->deselectAllRows();
        $this->cancelBulkAction();
        $this->resetPage();
    }
}
