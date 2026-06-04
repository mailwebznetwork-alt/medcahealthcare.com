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
     * @return list<WhatsAppClickNumber>
     */
    public function configuredNumbers(): array
    {
        $integration = $this->integration();
        if ($integration === null) {
            return $this->fallbackFromConfig();
        }

        $credentials = $this->credentialVault->decrypt(
            is_array($integration->credentials) ? $integration->credentials : []
        );
        $rows = $credentials['click_numbers'] ?? [];
        if (! is_array($rows)) {
            $rows = [];
        }

        $numbers = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $number = WhatsAppClickNumber::fromArray($row);
            if ($number !== null) {
                $numbers[] = $number;
            }
        }

        if ($numbers === []) {
            return $this->fallbackFromConfig();
        }

        usort($numbers, fn (WhatsAppClickNumber $a, WhatsAppClickNumber $b): int => $a->sortOrder <=> $b->sortOrder);

        return array_slice($numbers, 0, self::MAX_NUMBERS);
    }

    public function primaryUrl(): string
    {
        $active = $this->activeNumbers();

        return $active[0]->waMeUrl() ?? (string) config('medca.whatsapp_url', 'https://wa.me/');
    }

    public function isFloatingButtonEnabled(): bool
    {
        $integration = $this->integration();
        if ($integration === null || ! $integration->is_enabled) {
            return $this->activeNumbers() !== [];
        }

        $credentials = $this->credentialVault->decrypt(
            is_array($integration->credentials) ? $integration->credentials : []
        );

        return (bool) ($credentials['floating_button_enabled'] ?? true);
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
            ]);
            if ($number !== null) {
                $out[] = $number->toArray();
            }
        }

        return $out;
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

    /**
     * @return list<WhatsAppClickNumber>
     */
    private function fallbackFromConfig(): array
    {
        $url = (string) config('medca.whatsapp_url', '');
        if ($url === '' || ! preg_match('#wa\.me/(\d+)#', $url, $m)) {
            return [];
        }

        $message = '';
        if (preg_match('#[?&]text=([^&]+)#', $url, $msg)) {
            $message = rawurldecode($msg[1]);
        }

        $number = WhatsAppClickNumber::fromArray([
            'display_name' => (string) config('medca.name', 'Medca'),
            'phone' => $m[1],
            'default_message' => $message,
            'enabled' => true,
            'sort_order' => 1,
        ]);

        return $number !== null ? [$number] : [];
    }
}
