<?php

namespace App\Livewire\Location;

use App\Models\PinCode;
use App\Services\UserLocationService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class PincodeModal extends Component
{
    private const int SUGGESTION_MIN_LENGTH = 3;

    private const int SUGGESTION_LIMIT = 20;

    public bool $open = false;

    public string $pincode = '';

    /** @var list<array{pincode: string, area_name: string, city: string}> */
    public array $pincodeSuggestions = [];

    public bool $showPincodeSuggestions = false;

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
        $this->refreshPincodeSuggestions();
    }

    #[On('open-pincode-modal')]
    public function openFromEvent(): void
    {
        $this->openModal();
    }

    public function closeModal(): void
    {
        $this->open = false;
        $this->hidePincodeSuggestions();
    }

    public function updatedPincode(): void
    {
        $this->resetErrorBag('pincode');
        $this->pincode = app(UserLocationService::class)->normalizePincode($this->pincode);
        $this->refreshPincodeSuggestions();
    }

    public function refreshPincodeSuggestions(): void
    {
        $digits = $this->pincode;

        if (strlen($digits) < self::SUGGESTION_MIN_LENGTH || ! Schema::hasTable('pin_codes')) {
            $this->pincodeSuggestions = [];
            $this->showPincodeSuggestions = false;

            return;
        }

        $this->pincodeSuggestions = PinCode::query()
            ->where('is_active', true)
            ->where('pincode', 'like', $digits.'%')
            ->orderBy('pincode')
            ->limit(self::SUGGESTION_LIMIT)
            ->get(['pincode', 'area_name', 'city'])
            ->map(static fn (PinCode $record): array => [
                'pincode' => (string) $record->pincode,
                'area_name' => (string) $record->area_name,
                'city' => (string) $record->city,
            ])
            ->all();

        $this->showPincodeSuggestions = strlen($digits) >= self::SUGGESTION_MIN_LENGTH && strlen($digits) < 6;
    }

    public function selectPincode(string $pincode): void
    {
        $this->pincode = app(UserLocationService::class)->normalizePincode($pincode);
        $this->hidePincodeSuggestions();
    }

    public function hidePincodeSuggestions(): void
    {
        $this->showPincodeSuggestions = false;
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
        return view('livewire.location.pincode-modal');
    }
}
