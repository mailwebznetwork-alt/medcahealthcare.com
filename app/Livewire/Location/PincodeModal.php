<?php

namespace App\Livewire\Location;

use App\Models\PinCode;
use App\Services\Discovery\PincodeRedirectResolver;
use App\Services\UserLocationService;
use Illuminate\Http\Request;
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

    public string $redirectContextPath = '';

    public function mount(): void
    {
        $this->open = (bool) ViewFacade::shared('locationRequired', false);
        $this->redirectContextPath = '/'.ltrim(request()->path(), '/');
        $current = app(UserLocationService::class)->currentPincode();
        if ($current !== null) {
            $this->pincode = $current;
        }
    }

    public function openModal(?string $contextPath = null): void
    {
        $this->refreshRedirectContext($contextPath);
        $this->open = true;
        $this->refreshPincodeSuggestions();
    }

    #[On('open-pincode-modal')]
    public function openFromEvent(?string $contextPath = null): void
    {
        $this->openModal($contextPath);
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

    public function savePincode(UserLocationService $location, PincodeRedirectResolver $redirects): void
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

        $redirectUrl = $redirects->resolveAfterSwitch($this->redirectContextRequest(), $resolved);

        // Full page load required — wire:navigate does not re-render service-location heroes.
        $this->redirect($redirectUrl, navigate: false);
    }

    private function refreshRedirectContext(?string $contextPath = null): void
    {
        if (is_string($contextPath) && $contextPath !== '') {
            $this->redirectContextPath = '/'.ltrim($contextPath, '/');

            return;
        }

        $referer = request()->headers->get('referer');
        if (is_string($referer) && $referer !== '') {
            $refererPath = parse_url($referer, PHP_URL_PATH);
            if (is_string($refererPath) && $refererPath !== '' && ! str_contains($refererPath, '/livewire')) {
                $this->redirectContextPath = $refererPath;

                return;
            }
        }

        $this->redirectContextPath = '/'.ltrim(request()->path(), '/');
    }

    private function redirectContextRequest(): Request
    {
        $path = trim($this->redirectContextPath, '/');

        if ($path !== '' && $path !== 'livewire' && ! str_starts_with($path, 'livewire/')) {
            return Request::create('/'.$path, 'GET');
        }

        $referer = request()->headers->get('referer');
        if (is_string($referer) && $referer !== '') {
            $refererPath = parse_url($referer, PHP_URL_PATH);
            if (is_string($refererPath) && $refererPath !== '') {
                return Request::create($refererPath, 'GET');
            }
        }

        return request();
    }

    public function render(): View
    {
        return view('livewire.location.pincode-modal');
    }
}
