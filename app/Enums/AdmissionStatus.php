<?php

namespace App\Enums;

enum AdmissionStatus: string
{
    case Pending = 'pending';
    case Admitted = 'admitted';
    case Discharged = 'discharged';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Admitted => 'Admitted',
            self::Discharged => 'Discharged',
            self::Cancelled => 'Cancelled',
        };
    }
}
