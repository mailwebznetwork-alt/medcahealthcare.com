<?php

namespace App\Livewire\Concerns;

use App\Services\ActivityLogService;
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
            $resourceConfig = config('bulk_actions.resources')[$this->bulkResourceKey()] ?? [];
            $module = is_array($resourceConfig) ? (string) ($resourceConfig['module'] ?? 'operations') : 'operations';

            app(ActivityLogService::class)->log(
                'bulk_delete_blocked',
                $module,
                strtoupper($this->bulkResourceKey()).' bulk delete blocked: confirmation text mismatch.',
            );

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

        try {
            $result = $service->execute(
                $this->bulkResourceKey(),
                $ids,
                $this->bulkPendingAction,
                auth()->user(),
            );
        } catch (\Throwable $exception) {
            report($exception);

            $resourceConfig = config('bulk_actions.resources')[$this->bulkResourceKey()] ?? [];
            $module = is_array($resourceConfig) ? (string) ($resourceConfig['module'] ?? 'operations') : 'operations';

            app(ActivityLogService::class)->log(
                'bulk_action_failed',
                $module,
                strtoupper($this->bulkResourceKey()).' bulk '.$this->bulkPendingAction.' failed: '.$exception->getMessage(),
            );

            session()->flash('error', __('Bulk action failed. Please try again or delete in smaller batches.'));
            $this->cancelBulkAction();

            return;
        }

        session()->flash('status', $result['message']);
        $this->deselectAllRows();
        $this->cancelBulkAction();
        $this->resetPage();
    }

    public function openBulkModify(): void
    {
        $ids = $this->resolvedBulkSelectedIds();
        if (count($ids) !== 1) {
            session()->flash('error', __('Select exactly one row to modify.'));

            return;
        }

        $config = config('bulk_actions.resources')[$this->bulkResourceKey()] ?? null;
        if (is_array($config) && ($config['inline_modify'] ?? false) && method_exists($this, 'startEdit')) {
            $this->startEdit($ids[0]);
            $this->deselectAllRows();

            return;
        }

        $route = is_array($config) ? ($config['edit_route'] ?? null) : null;
        if (! is_string($route) || $route === '') {
            session()->flash('error', __('Modify is not available for this list.'));

            return;
        }

        $this->redirect(route($route, $ids[0]));
    }
}
