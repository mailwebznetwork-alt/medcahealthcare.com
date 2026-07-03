<?php

namespace App\Livewire\Location;

use App\Services\Discovery\PincodeRedirectResolver;
use App\Services\PinCodes\PinCodeSuggestionCatalog;
use App\Services\UserLocationService;
use Illuminate\Http\Request;
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

    public ?string $matchedAreaName = null;

    public ?string $matchedCity = null;

    /** null = incomplete pincode; true/false when 6 digits entered */
    public ?bool $pincodeIsServiceable = null;

    public bool $forceOpen = false;

    public string $redirectContextPath = '';

    public function mount(): void
    {
        $this->forceOpen = (bool) ViewFacade::shared('locationRequired', false);
        $this->open = $this->forceOpen;
        $this->redirectContextPath = '/'.ltrim(request()->path(), '/');
        $this->syncPincodeFromSession();
    }

    public function openModal(?string $contextPath = null): void
    {
        $this->refreshRedirectContext($contextPath);
        $this->syncPincodeFromSession();
        $this->resetErrorBag();
        $this->hidePincodeSuggestions();
        $this->forceOpen = ! app(UserLocationService::class)->hasPincode();
        $this->open = true;

        if ($this->pincode !== '') {
            $this->refreshPincodeLookup();
        }
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

    public function dismissLabel(): string
    {
        if ($this->forceOpen || ! app(UserLocationService::class)->hasPincode()) {
            return (string) __('Later');
        }

        return (string) __('Cancel');
    }

    public function updatedPincode(): void
    {
        $this->resetErrorBag('pincode');
        $this->pincode = app(UserLocationService::class)->normalizePincode($this->pincode);
        $this->refreshPincodeLookup();
    }

    public function refreshPincodeSuggestions(): void
    {
        $this->refreshPincodeLookup();
    }

    public function refreshPincodeLookup(): void
    {
        $this->resetPincodeMatch();
        $digits = $this->pincode;
        $catalog = app(PinCodeSuggestionCatalog::class);

        if (strlen($digits) < self::SUGGESTION_MIN_LENGTH) {
            $this->pincodeSuggestions = [];
            $this->showPincodeSuggestions = false;

            return;
        }

        if ($catalog->rows() === [] && strlen($digits) === 6) {
            $this->pincodeIsServiceable = app(UserLocationService::class)->resolveServiceablePincode($digits) !== null;

            return;
        }

        if (strlen($digits) === 6) {
            $this->pincodeSuggestions = [];
            $this->showPincodeSuggestions = false;

            $record = $catalog->findExact($digits);

            if ($record !== null) {
                $this->matchedAreaName = $record['area_name'];
                $this->matchedCity = $record['city'];
                $this->pincodeIsServiceable = true;
            } else {
                $this->pincodeIsServiceable = false;
            }

            return;
        }

        $this->pincodeSuggestions = $catalog->matchPrefix($digits, self::SUGGESTION_LIMIT);
        $this->showPincodeSuggestions = true;
    }

    private function resetPincodeMatch(): void
    {
        $this->matchedAreaName = null;
        $this->matchedCity = null;
        $this->pincodeIsServiceable = null;
    }

    public function selectPincode(string $pincode): void
    {
        $this->pincode = app(UserLocationService::class)->normalizePincode($pincode);
        $this->refreshPincodeLookup();
    }

    public function hidePincodeSuggestions(): void
    {
        $this->showPincodeSuggestions = false;
    }

    public function savePincode(UserLocationService $location, PincodeRedirectResolver $redirects): void
    {
        $this->pincode = $location->normalizePincode($this->pincode);

        $this->validate([
            'pincode' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $resolved = $location->setManualPincode($this->pincode);
        if ($resolved === null) {
            $this->addError(
                'pincode',
                app(\App\Services\Seo\LocalityContextResolver::class)->pincodeRejectionHint()
            );

            return;
        }

        session()->flash('status', __('Location updated to pincode :pin.', ['pin' => $resolved]));
        session()->save();

        $this->open = false;
        $this->dispatch('pincode-updated', pincode: $resolved);

        $redirectUrl = $this->redirectUrlWithPinRefresh(
            $redirects->resolveAfterSwitch($this->redirectContextRequest(), $resolved),
            $resolved,
        );

        if (app()->environment('testing')) {
            $this->redirect($redirectUrl, navigate: false);

            return;
        }

        // Hard navigation avoids Livewire redirect edge cases on mobile and with hash URLs.
        $this->js('window.location.assign('.json_encode($redirectUrl, JSON_THROW_ON_ERROR).')');
        $this->skipRender();
    }

    private function redirectUrlWithPinRefresh(string $url, string $pincode): string
    {
        $parts = parse_url($url);
        $query = [];

        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $query['pin'] = $pincode;

        $path = $parts['path'] ?? '/';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        if (isset($parts['scheme'], $parts['host'])) {
            $port = isset($parts['port']) ? ':'.$parts['port'] : '';

            return $parts['scheme'].'://'.$parts['host'].$port.$path.'?'.http_build_query($query).$fragment;
        }

        return $path.'?'.http_build_query($query).$fragment;
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

    private function syncPincodeFromSession(): void
    {
        $current = app(UserLocationService::class)->currentPincode();
        if ($current !== null) {
            $this->pincode = $current;
            $this->refreshPincodeLookup();
        }
    }

    public function render(): View
    {
        return view('livewire.location.pincode-modal');
    }
}
