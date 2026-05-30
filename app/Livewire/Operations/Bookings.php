<?php

namespace App\Livewire\Operations;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Jobs\ScoreLeadPayloadJob;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\PinCode;
use App\Models\User;
use App\Services\Integrations\OutboundWebhookDispatcher;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Bookings extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $filterStatus = '';

    public string $filterSource = '';

    public bool $showLeadModal = false;

    public bool $showNoteModal = false;

    public ?int $editingId = null;

    public ?int $noteLeadId = null;

    public string $noteText = '';

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $service = '';

    public string $message = '';

    public string $source = '';

    public string $campaign = '';

    public ?int $pin_code_id = null;

    public string $status = '';

    public ?int $assigned_to = null;

    public ?string $follow_up_date = null;

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterSource(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Lead::class);
    }

    public function openCreate(): void
    {
        $this->authorize('create', Lead::class);
        $this->editingId = null;
        $this->resetLeadForm();
        $this->status = LeadStatus::New->value;
        $this->source = LeadSource::Organic->value;
        $this->showLeadModal = true;
    }

    public function openEdit(int $id): void
    {
        $lead = Lead::query()->findOrFail($id);
        $this->authorize('update', $lead);
        $this->editingId = $lead->id;
        $this->name = $lead->name;
        $this->phone = $lead->phone;
        $this->email = (string) ($lead->email ?? '');
        $this->service = $lead->service;
        $this->message = (string) ($lead->message ?? '');
        $this->source = $lead->source->value;
        $this->campaign = (string) ($lead->campaign ?? '');
        $this->pin_code_id = $lead->pin_code_id;
        $this->status = $lead->status->value;
        $this->assigned_to = $lead->assigned_to;
        $this->follow_up_date = $lead->follow_up_date?->format('Y-m-d');
        $this->showLeadModal = true;
    }

    public function saveLead(): void
    {
        $this->pin_code_id = $this->pin_code_id ?: null;
        $this->assigned_to = $this->assigned_to ?: null;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'service' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'source' => ['required', 'string'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'pin_code_id' => ['nullable', 'integer', 'exists:pin_codes,id'],
            'status' => ['required', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'follow_up_date' => ['nullable', 'date'],
        ];
        $this->validate($rules);

        $payload = [
            'name' => strip_tags(trim($this->name)),
            'phone' => trim($this->phone),
            'email' => $this->email !== '' ? trim($this->email) : null,
            'service' => strip_tags(trim($this->service)),
            'message' => $this->message !== '' ? strip_tags(trim($this->message)) : null,
            'source' => LeadSource::from($this->source),
            'campaign' => $this->campaign !== '' ? strip_tags(trim($this->campaign)) : null,
            'pin_code_id' => $this->pin_code_id,
            'status' => LeadStatus::from($this->status),
            'assigned_to' => $this->assigned_to,
            'follow_up_date' => $this->follow_up_date,
        ];

        if ($this->editingId === null) {
            $this->authorize('create', Lead::class);
            $lead = Lead::query()->create($payload);
            ScoreLeadPayloadJob::dispatch($lead);
            $this->maybeDispatchServiceBooked(null, $lead);
        } else {
            $lead = Lead::query()->findOrFail($this->editingId);
            $previousStatus = $lead->status;
            $this->authorize('update', $lead);
            $lead->update($payload);
            $lead->refresh();
            $this->maybeDispatchServiceBooked($previousStatus, $lead);
        }

        $this->showLeadModal = false;
        $this->resetLeadForm();
        $this->resetPage();
    }

    public function deleteLead(int $id): void
    {
        $lead = Lead::query()->findOrFail($id);
        $this->authorize('delete', $lead);
        $lead->delete();
        $this->resetPage();
    }

    public function updateStatus(int $leadId, string $status): void
    {
        $lead = Lead::query()->findOrFail($leadId);
        $this->authorize('update', $lead);
        $previousStatus = $lead->status;
        $lead->update(['status' => LeadStatus::from($status)]);
        $lead->refresh();
        $this->maybeDispatchServiceBooked($previousStatus, $lead);
    }

    private function maybeDispatchServiceBooked(?LeadStatus $previous, Lead $lead): void
    {
        if ($lead->status !== LeadStatus::Converted) {
            return;
        }

        if ($previous !== null && $previous === LeadStatus::Converted) {
            return;
        }

        app(OutboundWebhookDispatcher::class)->dispatch('service.booked', [
            'lead_id' => $lead->id,
            'uuid' => $lead->uuid,
            'service' => $lead->service,
            'status' => $lead->status->value,
        ]);
    }

    public function updateAssigned(int $leadId, mixed $userId): void
    {
        $lead = Lead::query()->findOrFail($leadId);
        $this->authorize('update', $lead);
        $uid = $userId === null || $userId === '' ? null : (int) $userId;
        $lead->update(['assigned_to' => $uid]);
    }

    public function updateFollowUp(int $leadId, mixed $date): void
    {
        $lead = Lead::query()->findOrFail($leadId);
        $this->authorize('update', $lead);
        $d = is_string($date) && $date !== '' ? $date : null;
        $lead->update(['follow_up_date' => $d]);
    }

    public function openNote(int $leadId): void
    {
        $lead = Lead::query()->findOrFail($leadId);
        $this->authorize('update', $lead);
        $this->noteLeadId = $leadId;
        $this->noteText = '';
        $this->showNoteModal = true;
    }

    public function saveNote(): void
    {
        $this->validate([
            'noteText' => ['required', 'string', 'max:5000'],
        ]);

        $lead = Lead::query()->findOrFail((int) $this->noteLeadId);
        $this->authorize('update', $lead);

        LeadNote::query()->create([
            'lead_id' => $lead->id,
            'note' => strip_tags(trim($this->noteText)),
            'created_by' => (int) auth()->id(),
        ]);

        $this->showNoteModal = false;
        $this->noteLeadId = null;
        $this->noteText = '';
    }

    public function render(): View
    {
        $query = Lead::query()
            ->with('assignedUser')
            ->when($this->search !== '', function ($q): void {
                $s = '%'.$this->search.'%';
                $q->where(function ($q) use ($s): void {
                    $q->where('name', 'like', $s)
                        ->orWhere('phone', 'like', $s)
                        ->orWhere('email', 'like', $s)
                        ->orWhere('service', 'like', $s)
                        ->orWhere('campaign', 'like', $s);
                });
            })
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterSource !== '', fn ($q) => $q->where('source', $this->filterSource))
            ->latest();

        return view('livewire.operations.bookings', [
            'leads' => $query->paginate(15),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'pinCodes' => PinCode::query()->where('is_active', true)->orderBy('pincode')->limit(300)->get(['id', 'pincode', 'area_name']),
            'sources' => LeadSource::cases(),
            'statuses' => LeadStatus::cases(),
        ]);
    }

    protected function resetLeadForm(): void
    {
        $this->reset([
            'name', 'phone', 'email', 'service', 'message', 'campaign', 'pin_code_id',
            'assigned_to', 'follow_up_date',
        ]);
        $this->source = LeadSource::Organic->value;
        $this->status = LeadStatus::New->value;
        $this->follow_up_date = null;
    }
}
