<?php

namespace App\Livewire\Operations;

use App\Models\Lead;
use App\Models\LeadNote;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

class BookingsShow extends Component
{
    use AuthorizesRequests;

    public Lead $lead;

    public string $noteBody = '';

    public function mount(Lead $lead): void
    {
        $this->authorize('view', $lead);
        $this->lead = $lead->load(['assignedUser', 'pinCode', 'notes.author']);
    }

    public function addNote(): void
    {
        $this->authorize('update', $this->lead);

        $this->validate([
            'noteBody' => ['required', 'string', 'max:5000'],
        ]);

        LeadNote::query()->create([
            'lead_id' => $this->lead->id,
            'note' => strip_tags(trim($this->noteBody)),
            'created_by' => (int) auth()->id(),
        ]);

        $this->noteBody = '';
        $this->lead->refresh()->load(['notes.author', 'assignedUser', 'pinCode']);
    }

    public function render(): View
    {
        return view('livewire.operations.bookings-show');
    }
}
