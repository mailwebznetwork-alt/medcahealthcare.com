<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Support\WhatsAppClickNumber;
use Illuminate\Support\Facades\Schema;

class WhatsAppClickToChatService
{
    public function __construct(
        private readonly CredentialVault $credentialVault,
    ) {}

    public const string INTEGRATION_NAME = 'whatsapp';

    public const string BUSINESS_API_INTEGRATION_NAME = 'whatsapp_business';

    public const int MAX_NUMBERS = 5;

    /**
     * Canonical public WhatsApp number (digits only, with country code).
     * Always sourced from env/config/global content — never from broken credentials.
     */
    public function canonicalPhone(): string
    {
        $candidates = [
            env('MEDCA_WHATSAPP_NUMBER'),
            config('medca.whatsapp_number'),
            config('medca.whatsapp_url'),
            app(\App\Services\Deployment\GlobalContentVariableRepository::class)->resolved()['whatsapp'] ?? null,
            config('medca.phone_tel'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            if (preg_match('#wa\.me/(\d+)#', $candidate, $m) === 1) {
                $phone = WhatsAppClickNumber::normalizePhone($m[1]);
            } else {
                $phone = WhatsAppClickNumber::normalizePhone($candidate);
            }

            if ($phone !== '') {
                return $phone;
            }
        }

        return '918884999002';
    }

    /**
     * Clean click-to-chat URL (no prefilled text — avoids client-side "invalid number" errors).
     */
    public function canonicalUrl(): string
    {
        return 'https://wa.me/'.$this->canonicalPhone();
    }

    /**
     * @return list<WhatsAppClickNumber>
     */
    public function activeNumbers(): array
    {
        return array_values(array_filter(
            $this->configuredNumbers(),
            fn (WhatsAppClickNumber $n): bool => $n->enabled
        ));
    }

    /**
     * Public site always uses the canonical brand number from env/config.
     * Integration credentials are for admin display only and are synced via
     * syncCanonicalCredentials() — never trusted as the live click-to-chat source
     * (APP_KEY rotation previously turned ciphertext into a garbage wa.me link).
     *
     * @return list<WhatsAppClickNumber>
     */
    public function configuredNumbers(): array
    {
        return [$this->canonicalNumber()];
    }

    public function primaryUrl(): string
    {
        return $this->canonicalUrl();
    }

    public function isFloatingButtonEnabled(): bool
    {
        $integration = $this->integration();
        if ($integration === null || ! $integration->is_enabled) {
            return true;
        }

        $credentials = $this->credentialVault->decrypt(
            is_array($integration->credentials) ? $integration->credentials : []
        );

        return filter_var($credentials['floating_button_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
    }

    public function isClickToChatEnabled(): bool
    {
        $integration = $this->integration();
        if ($integration === null) {
            return true;
        }

        return $integration->is_enabled && $this->activeNumbers() !== [];
    }

    public function businessApiIntegration(): ?Integration
    {
        if (! Schema::hasTable('integrations')) {
            return null;
        }

        return Integration::query()
            ->where('name', self::BUSINESS_API_INTEGRATION_NAME)
            ->with('accounts')
            ->first();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function sanitizeClickNumbers(array $rows): array
    {
        $out = [];
        foreach (array_slice(array_values($rows), 0, self::MAX_NUMBERS) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $number = WhatsAppClickNumber::fromArray([
                ...$row,
                'sort_order' => $row['sort_order'] ?? ($index + 1),
                // Never persist prefilled messages — they break some WhatsApp clients.
                'default_message' => '',
            ]);
            if ($number !== null) {
                $out[] = $number->toArray();
            }
        }

        if ($out === []) {
            $out[] = $this->canonicalNumber()->toArray();
        }

        return $out;
    }

    /**
     * Persist the canonical brand number into the integration credentials
     * so admin UI and public site stay in sync after key rotations.
     */
    public function syncCanonicalCredentials(): void
    {
        $integration = $this->integration();
        if ($integration === null) {
            return;
        }

        $payload = [
            'floating_button_enabled' => true,
            'click_numbers' => [
                $this->canonicalNumber()->toArray(),
            ],
        ];

        $integration->credentials = $this->credentialVault->encrypt($payload);
        $integration->is_enabled = true;
        $integration->save();
    }

    private function integration(): ?Integration
    {
        if (! Schema::hasTable('integrations')) {
            return null;
        }

        return Integration::query()
            ->where('name', self::INTEGRATION_NAME)
            ->first();
    }

    private function canonicalNumber(): WhatsAppClickNumber
    {
        return new WhatsAppClickNumber(
            displayName: (string) config('medca.brand_name', 'Medca Healthcare'),
            phone: $this->canonicalPhone(),
            defaultMessage: '',
            enabled: true,
            sortOrder: 0,
        );
    }
}
