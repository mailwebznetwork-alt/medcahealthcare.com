<?php

namespace App\Livewire\Operations;

use App\Models\Admission;
use App\Models\Lead;
use App\Models\PinCode;
use App\Models\RevenueEvent;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class RevenueEvents extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public ?int $admission_id = null;

    public ?int $lead_id = null;

    public ?int $service_id = null;

    public ?int $pin_code_id = null;

    public ?int $service_category_id = null;

    public string $amount = '';

    public string $currency = 'INR';

    public string $label = '';

    public string $notes = '';

    public ?string $recorded_at = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('viewAny', RevenueEvent::class);
        $this->recorded_at = now()->format('Y-m-d\TH:i');
    }

    public function openCreate(): void
    {
        $this->authorize('create', RevenueEvent::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $event = RevenueEvent::query()->findOrFail($id);
        $this->authorize('update', $event);
        $this->editingId = $event->id;
        $this->admission_id = $event->admission_id;
        $this->lead_id = $event->lead_id;
        $this->service_id = $event->service_id;
        $this->pin_code_id = $event->pin_code_id;
        $this->service_category_id = $event->service_category_id;
        $this->amount = (string) $event->amount;
        $this->currency = $event->currency;
        $this->label = (string) ($event->label ?? '');
        $this->notes = (string) ($event->notes ?? '');
        $this->recorded_at = $event->recorded_at?->format('Y-m-d\TH:i');
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'admission_id' => ['nullable', 'integer', 'exists:admissions,id'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'pin_code_id' => ['nullable', 'integer', 'exists:pin_codes,id'],
            'service_category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:3'],
            'label' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'recorded_at' => ['required', 'date'],
        ]);

        $event = $this->editingId
            ? RevenueEvent::query()->findOrFail($this->editingId)
            : new RevenueEvent;

        $this->authorize($this->editingId ? 'update' : 'create', $event);

        if ($data['admission_id'] ?? null) {
            $admission = Admission::query()->find($data['admission_id']);
            if ($admission !== null) {
                $data['lead_id'] = $data['lead_id'] ?? $admission->lead_id;
                $data['service_id'] = $data['service_id'] ?? $admission->service_id;
                $data['pin_code_id'] = $data['pin_code_id'] ?? $admission->pin_code_id;
                $data['marketing_attribution_session_id'] = $admission->marketing_attribution_session_id;
            }
        } elseif ($data['lead_id'] ?? null) {
            $lead = Lead::query()->find($data['lead_id']);
            if ($lead !== null) {
                $data['service_id'] = $data['service_id'] ?? $lead->service_id;
                $data['pin_code_id'] = $data['pin_code_id'] ?? $lead->pin_code_id;
                $data['marketing_attribution_session_id'] = $lead->marketing_attribution_session_id;
            }
        }

        $event->fill($data);
        $event->recorded_by = auth()->id();
        $event->save();

        $this->showModal = false;
        $this->resetForm();
    }

    public function deleteEvent(int $id): void
    {
        $event = RevenueEvent::query()->findOrFail($id);
        $this->authorize('delete', $event);
        $event->delete();
    }

    public function render(): View
    {
        $query = RevenueEvent::query()->with(['service', 'pinCode', 'lead'])->orderByDesc('recorded_at');

        if ($this->search !== '') {
            $needle = '%'.$this->search.'%';
            $query->where(function ($q) use ($needle): void {
                $q->where('label', 'like', $needle)->orWhere('notes', 'like', $needle);
            });
        }

        return view('livewire.operations.revenue-events', [
            'events' => $query->paginate(20),
            'admissions' => Admission::query()->orderByDesc('created_at')->limit(50)->get(['id', 'patient_name']),
            'leads' => Lead::query()->orderByDesc('created_at')->limit(50)->get(['id', 'name']),
            'services' => Service::query()->where('is_active', true)->orderBy('title')->get(['id', 'title']),
            'pincodes' => PinCode::query()->where('is_active', true)->orderBy('pincode')->limit(100)->get(['id', 'pincode']),
            'categories' => ServiceCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->admission_id = null;
        $this->lead_id = null;
        $this->service_id = null;
        $this->pin_code_id = null;
        $this->service_category_id = null;
        $this->amount = '';
        $this->currency = 'INR';
        $this->label = '';
        $this->notes = '';
        $this->recorded_at = now()->format('Y-m-d\TH:i');
    }
}
