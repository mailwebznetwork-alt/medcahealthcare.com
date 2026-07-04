<?php

namespace App\Support;

final class WhatsAppClickNumber
{
    public function __construct(
        public readonly string $displayName,
        public readonly string $phone,
        public readonly string $defaultMessage,
        public readonly bool $enabled,
        public readonly int $sortOrder,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): ?self
    {
        $phone = self::normalizePhone((string) ($row['phone'] ?? $row['whatsapp_number'] ?? ''));
        if ($phone === '') {
            return null;
        }

        return new self(
            displayName: trim((string) ($row['display_name'] ?? $row['label'] ?? __('WhatsApp'))),
            phone: $phone,
            // Prefill text breaks some WhatsApp clients ("phone number shared via url is invalid").
            defaultMessage: '',
            enabled: filter_var($row['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            sortOrder: max(0, min(99, (int) ($row['sort_order'] ?? 0))),
        );
    }

    public static function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        // Reject ciphertext / garbage left after failed credential decrypt
        // (e.g. APP_KEY rotation). E.164 mobiles are 10–15 digits.
        if ($digits === '' || strlen($digits) < 10 || strlen($digits) > 15) {
            return '';
        }

        // Indian 10-digit mobiles → prefix country code for wa.me
        if (preg_match('/^[6-9]\d{9}$/', $digits) === 1) {
            return '91'.$digits;
        }

        return $digits;
    }

    public function waMeUrl(): string
    {
        $base = 'https://wa.me/'.$this->phone;
        if ($this->defaultMessage === '') {
            return $base;
        }

        return $base.'?text='.rawurlencode($this->defaultMessage);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'display_name' => $this->displayName,
            'phone' => $this->phone,
            'default_message' => $this->defaultMessage,
            'enabled' => $this->enabled,
            'sort_order' => $this->sortOrder,
        ];
    }
}
