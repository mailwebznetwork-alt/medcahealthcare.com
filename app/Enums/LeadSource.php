<?php

namespace App\Enums;

enum LeadSource: string
{
    case GoogleAds = 'google_ads';
    case MetaAds = 'meta_ads';
    case Organic = 'organic';
    case WhatsApp = 'whatsapp';
    case Call = 'call';
    case Gmb = 'gmb';

    public function label(): string
    {
        return match ($this) {
            self::GoogleAds => 'Google Ads',
            self::MetaAds => 'Meta Ads',
            self::Organic => 'Organic',
            self::WhatsApp => 'WhatsApp',
            self::Call => 'Call',
            self::Gmb => 'GMB',
        };
    }

    public static function tryFromLabelOrKey(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        $t = trim($value);
        foreach (self::cases() as $case) {
            if (strcasecmp($case->value, $t) === 0 || strcasecmp($case->name, $t) === 0) {
                return $case;
            }
            if (strcasecmp($case->label(), $t) === 0) {
                return $case;
            }
        }

        return null;
    }
}
