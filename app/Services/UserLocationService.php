<?php

namespace App\Services;

use App\Models\PinCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Throwable;

class UserLocationService
{
    public function sessionKey(): string
    {
        return (string) config('location.session_key', 'medca.detected_pincode');
    }

    public function hasPincode(): bool
    {
        return $this->currentPincode() !== null;
    }

    public function currentPincode(): ?string
    {
        $authUser = auth()->user();
        if ($authUser instanceof User && filled($authUser->pincode)) {
            return $this->normalizePincode((string) $authUser->pincode);
        }

        $sessionPin = Session::get($this->sessionKey());

        return is_string($sessionPin) && $sessionPin !== ''
            ? $this->normalizePincode($sessionPin)
            : null;
    }

    public function currentPinCodeRecord(): ?PinCode
    {
        $pincode = $this->currentPincode();
        if ($pincode === null || ! Schema::hasTable('pin_codes')) {
            return null;
        }

        return PinCode::query()
            ->where('pincode', $pincode)
            ->where('is_active', true)
            ->first();
    }

    public function detectFromIp(Request $request): ?string
    {
        if (Session::get(config('location.ip_attempted_session_key'))) {
            return $this->currentPincode();
        }

        Session::put(config('location.ip_attempted_session_key'), true);

        $ip = $request->ip();
        if ($ip === null || $this->isPrivateIp($ip)) {
            return $this->applyDefaultIfServiceable();
        }

        $url = str_replace('{ip}', urlencode($ip), (string) config('location.ip_lookup_url'));

        try {
            $response = Http::timeout((int) config('location.ip_lookup_timeout', 4))
                ->get($url);

            if (! $response->successful()) {
                return $this->applyDefaultIfServiceable();
            }

            $zip = $response->json('zip');
            if (is_string($zip) && $zip !== '') {
                $resolved = $this->resolveServiceablePincode($zip);
                if ($resolved !== null) {
                    $this->rememberPincode($resolved);

                    return $resolved;
                }
            }
        } catch (Throwable) {
            // Fall through to default.
        }

        return $this->applyDefaultIfServiceable();
    }

    public function detectFromCoordinates(float $latitude, float $longitude): ?string
    {
        try {
            $response = Http::timeout(6)
                ->withHeaders(['User-Agent' => config('app.name', 'MedcaHealthcare').'/1.0'])
                ->get((string) config('location.reverse_geocode_url'), [
                    'format' => 'json',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'addressdetails' => 1,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $postcode = data_get($response->json(), 'address.postcode');
            if (is_string($postcode) && $postcode !== '') {
                $resolved = $this->resolveServiceablePincode($postcode);
                if ($resolved !== null) {
                    $this->rememberPincode($resolved);

                    return $resolved;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    public function setManualPincode(string $pincode): ?string
    {
        $resolved = $this->resolveServiceablePincode($pincode);
        if ($resolved === null) {
            return null;
        }

        $this->rememberPincode($resolved);

        return $resolved;
    }

    public function rememberPincode(string $pincode): void
    {
        $normalized = $this->normalizePincode($pincode);
        Session::put($this->sessionKey(), $normalized);

        $user = auth()->user();
        if ($user instanceof User) {
            $user->forceFill(['pincode' => $normalized])->save();
        }
    }

    public function normalizePincode(string $pincode): string
    {
        return preg_replace('/\D/', '', trim($pincode)) ?? '';
    }

    public function resolveServiceablePincode(string $pincode): ?string
    {
        $normalized = $this->normalizePincode($pincode);
        if (strlen($normalized) !== 6) {
            return null;
        }

        if (! Schema::hasTable('pin_codes')) {
            return $normalized;
        }

        $record = PinCode::query()
            ->where('pincode', $normalized)
            ->where('is_active', true)
            ->first();

        if ($record === null) {
            return null;
        }

        return $normalized;
    }

    private function applyDefaultIfServiceable(): ?string
    {
        $default = (string) config('location.default_pincode', '');
        if ($default === '') {
            return null;
        }

        $resolved = $this->resolveServiceablePincode($default);
        if ($resolved !== null) {
            $this->rememberPincode($resolved);
        }

        return $resolved;
    }

    private function isPrivateIp(string $ip): bool
    {
        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
