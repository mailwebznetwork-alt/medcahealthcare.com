<?php

namespace App\Enums;

enum LeadAttributionBucket: string
{
    case Organic = 'organic';
    case GoogleAds = 'google_ads';
    case MetaAds = 'meta_ads';
    case Gbp = 'gbp';
    case Direct = 'direct';
    case Referral = 'referral';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Organic => 'Organic',
            self::GoogleAds => 'Google Ads',
            self::MetaAds => 'Meta Ads',
            self::Gbp => 'Google Business Profile',
            self::Direct => 'Direct',
            self::Referral => 'Referral',
            self::Unknown => 'Unknown',
        };
    }
}
