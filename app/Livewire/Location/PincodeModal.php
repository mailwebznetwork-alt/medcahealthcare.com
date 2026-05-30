<?php

namespace App\Livewire\Location;

use App\Models\PinCode;
use App\Services\UserLocationService;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class PincodeModal extends Component
{
    public bool $open = false;

    public string $pincode = '';

    public function mount(): void
    {
        $this->open = (bool) ViewFacade::shared('locationRequired', false);
        $current = app(UserLocationService::class)->currentPincode();
        if ($current !== null) {
            $this->pincode = $current;
        }
    }

    public function openModal(): void
    {
        $this->open = true;
    }

    #[On('open-pincode-modal')]
    public function openFromEvent(): void
    {
        $this->openModal();
    }

    public function closeModal(): void
    {
        $this->open = false;
    }

    public function savePincode(UserLocationService $location): void
    {
        $this->validate([
            'pincode' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $resolved = $location->setManualPincode($this->pincode);
        if ($resolved === null) {
            $this->addError('pincode', __('We do not service that pincode yet.'));

            return;
        }

        $this->open = false;
        $this->dispatch('pincode-updated', pincode: $resolved);
        $this->js('window.location.reload()');
    }

    public function render(): View
    {
        $samples = PinCode::query()
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderBy('pincode')
            ->limit(8)
            ->get(['pincode', 'area_name', 'city']);

        return view('livewire.location.pincode-modal', [
            'samplePincodes' => $samples,
        ]);
    }
}
