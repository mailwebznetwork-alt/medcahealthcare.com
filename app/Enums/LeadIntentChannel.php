<?php

namespace App\Enums;

enum LeadIntentChannel: string
{
    case Calls = 'calls';
    case WhatsApp = 'whatsapp';
    case Forms = 'forms';

    public function label(): string
    {
        return match ($this) {
            self::Calls => 'Calls',
            self::WhatsApp => 'WhatsApp',
            self::Forms => 'Forms',
        };
    }
}
