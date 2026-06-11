<?php

namespace App\Livewire\Operations;

use App\Enums\AdmissionStatus;
use App\Models\Admission;
use App\Models\Lead;
use App\Models\PinCode;
use App\Models\Service;
use App\Services\Marketing\Attribution\AdmissionAttributionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Admissions extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $filterStatus = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public ?int $lead_id = null;

    public ?int $service_id = null;

    public ?int $pin_code_id = null;

    public string $patient_name = '';

    public string $patient_phone = '';

    public string $status = '';

    public string $notes = '';

    public ?string $admitted_at = null;

    public ?string $discharged_at = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('viewAny', Admission::class);
        $this->status = AdmissionStatus::Pending->value;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('create', Admission::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $admission = Admission::query()->findOrFail($id);
        $this->authorize('update', $admission);
        $this->editingId = $admission->id;
        $this->lead_id = $admission->lead_id;
        $this->service_id = $admission->service_id;
        $this->pin_code_id = $admission->pin_code_id;
        $this->patient_name = $admission->patient_name;
        $this->patient_phone = (string) ($admission->patient_phone ?? '');
        $this->status = $admission->status->value;
        $this->notes = (string) ($admission->notes ?? '');
        $this->admitted_at = $admission->admitted_at?->format('Y-m-d\TH:i');
        $this->discharged_at = $admission->discharged_at?->format('Y-m-d\TH:i');
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'pin_code_id' => ['nullable', 'integer', 'exists:pin_codes,id'],
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'admitted_at' => ['nullable', 'date'],
            'discharged_at' => ['nullable', 'date'],
        ]);

        $admission = $this->editingId
            ? Admission::query()->findOrFail($this->editingId)
            : new Admission;

        $this->authorize($this->editingId ? 'update' : 'create', $admission);

        $lead = isset($data['lead_id']) ? Lead::query()->find($data['lead_id']) : null;
        $admission->fill($data);
        $admission->recorded_by = auth()->id();
        app(AdmissionAttributionService::class)->applyFromLead($admission, $lead);

        if ($admission->status === AdmissionStatus::Admitted && $admission->admitted_at === null) {
            $admission->admitted_at = now();
        }

        if ($admission->status === AdmissionStatus::Discharged && $admission->discharged_at === null) {
            $admission->discharged_at = now();
        }

        $admission->save();
        $this->showModal = false;
        $this->resetForm();
    }

    public function deleteAdmission(int $id): void
    {
        $admission = Admission::query()->findOrFail($id);
        $this->authorize('delete', $admission);
        $admission->delete();
    }

    public function render(): View
    {
        $query = Admission::query()->with(['lead', 'service', 'pinCode'])->orderByDesc('created_at');

        if ($this->search !== '') {
            $needle = '%'.$this->search.'%';
            $query->where(function ($q) use ($needle): void {
                $q->where('patient_name', 'like', $needle)
                    ->orWhere('patient_phone', 'like', $needle);
            });
        }

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        return view('livewire.operations.admissions', [
            'admissions' => $query->paginate(20),
            'statuses' => AdmissionStatus::cases(),
            'leads' => Lead::query()->orderByDesc('created_at')->limit(100)->get(['id', 'name', 'phone']),
            'services' => Service::query()->where('is_active', true)->orderBy('title')->get(['id', 'title']),
            'pincodes' => PinCode::query()->where('is_active', true)->orderBy('pincode')->limit(200)->get(['id', 'pincode', 'area_name']),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->lead_id = null;
        $this->service_id = null;
        $this->pin_code_id = null;
        $this->patient_name = '';
        $this->patient_phone = '';
        $this->status = AdmissionStatus::Pending->value;
        $this->notes = '';
        $this->admitted_at = null;
        $this->discharged_at = null;
    }
}
