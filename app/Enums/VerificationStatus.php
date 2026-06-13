<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case Unverified = 'unverified';
    case Pending = 'pending';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Unverified',
            self::Pending => 'Pending verification',
            self::Verified => 'Verified',
        };
    }
}
