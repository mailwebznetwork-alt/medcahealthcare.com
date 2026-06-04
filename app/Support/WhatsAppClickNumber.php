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
            defaultMessage: trim((string) ($row['default_message'] ?? '')),
            enabled: (bool) ($row['enabled'] ?? true),
            sortOrder: max(0, min(99, (int) ($row['sort_order'] ?? 0))),
        );
    }

    public static function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

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
