<?php

namespace App\Services\Discovery;

use App\Services\UserLocationService;

/**
 * Pincode switch UX backend — session, availability, content refresh payloads.
 */
class ChangePincodeEngine
{
    public function __construct(
        private readonly UserLocationService $location,
        private readonly HealthcareDiscoveryEngine $discovery,
    ) {}

    /**
     * @return array{success: bool, pincode: string|null, pin_record: mixed, discovery: array<string, mixed>, message: string}
     */
    public function switch(string $pincode): array
    {
        $resolved = $this->location->setManualPincode($pincode);

        if ($resolved === null) {
            return [
                'success' => false,
                'pincode' => null,
                'pin_record' => null,
                'discovery' => [],
                'message' => app(\App\Services\Seo\LocalityContextResolver::class)->pincodeRejectionHint(),
            ];
        }

        $discovery = $this->discovery->discoverForPincode($resolved);

        return [
            'success' => true,
            'pincode' => $resolved,
            'pin_record' => $this->location->currentPinCodeRecord(),
            'discovery' => $discovery,
            'message' => __('Location updated to pincode :pin.', ['pin' => $resolved]),
        ];
    }

    public function current(): array
    {
        $pincode = $this->location->currentPincode();

        return [
            'pincode' => $pincode,
            'pin_record' => $this->location->currentPinCodeRecord(),
            'discovery' => $pincode !== null ? $this->discovery->discoverForPincode($pincode) : [],
        ];
    }

    /**
     * @return list<string>
     */
    public function searchServiceable(string $query, int $limit = 10): array
    {
        $normalized = $this->location->normalizePincode($query);
        if (strlen($normalized) < 3) {
            return [];
        }

        return \App\Models\PinCode::query()
            ->where('is_active', true)
            ->where('is_serviceable', true)
            ->where(function ($q) use ($normalized): void {
                $q->where('pincode', 'like', $normalized.'%')
                    ->orWhere('area_name', 'like', '%'.$normalized.'%');
            })
            ->orderBy('pincode')
            ->limit($limit)
            ->pluck('pincode')
            ->all();
    }
}
