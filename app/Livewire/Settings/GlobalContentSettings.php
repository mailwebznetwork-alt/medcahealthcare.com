<?php

namespace App\Livewire\Settings;

use App\Models\GlobalContentVariableSnapshot;
use App\Services\Deployment\GlobalContentVariableRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class GlobalContentSettings extends Component
{
    /** @var array<string, string> */
    public array $values = [];

    public string $import_json = '';

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(GlobalContentVariableRepository $repository): void
    {
        $user = auth()->user();
        if ($user === null || ! in_array(strtolower((string) $user->role), ['admin', 'super_admin'], true)) {
            abort(403);
        }

        if (! Schema::hasTable('global_content_variables')) {
            return;
        }

        foreach ($repository->forEditor() as $key => $row) {
            $this->values[$key] = $row['value'];
        }
    }

    public function save(GlobalContentVariableRepository $repository): void
    {
        $repository->sync($this->values, auth()->user());
        $this->statusMessage = __('Global content variables saved. Linked pages and blocks will use updated values on next render.');
        $this->errorMessage = null;
    }

    public function saveVersion(GlobalContentVariableRepository $repository): void
    {
        $repository->sync($this->values, auth()->user());
        $snapshot = $repository->createSnapshot(auth()->user());
        $this->statusMessage = __('Saved and version :v recorded.', ['v' => $snapshot->version]);
    }

    public function exportJson(GlobalContentVariableRepository $repository): void
    {
        $this->import_json = json_encode($repository->exportPayload(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->statusMessage = __('Exported to JSON field below.');
    }

    public function importJson(GlobalContentVariableRepository $repository): void
    {
        $payload = json_decode($this->import_json, true);
        if (! is_array($payload)) {
            $this->errorMessage = __('Invalid JSON.');

            return;
        }

        $repository->importPayload($payload, auth()->user());
        foreach ($repository->forEditor() as $key => $row) {
            $this->values[$key] = $row['value'];
        }
        $this->statusMessage = __('Variables imported.');
        $this->errorMessage = null;
    }

    public function restoreVersion(int $snapshotId, GlobalContentVariableRepository $repository): void
    {
        $snapshot = GlobalContentVariableSnapshot::query()->find($snapshotId);
        if ($snapshot === null) {
            $this->errorMessage = __('Version not found.');

            return;
        }

        $repository->restoreSnapshot($snapshot, auth()->user());
        foreach ($repository->forEditor() as $key => $row) {
            $this->values[$key] = $row['value'];
        }
        $this->statusMessage = __('Restored version :v.', ['v' => $snapshot->version]);
    }

    public function render(GlobalContentVariableRepository $repository): View
    {
        return view('livewire.settings.global-content-settings', [
            'grouped' => $repository->forEditorGrouped(),
            'ready' => Schema::hasTable('global_content_variables'),
            'preview' => $repository->previewSample(),
            'snapshots' => $repository->snapshots(),
        ]);
    }
}
