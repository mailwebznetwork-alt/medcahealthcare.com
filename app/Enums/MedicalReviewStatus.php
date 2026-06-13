<?php

namespace App\Enums;

enum MedicalReviewStatus: string
{
    case Draft = 'draft';
    case PendingMedical = 'pending_medical';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingMedical => 'Pending medical review',
            self::Approved => 'Medically approved',
            self::Rejected => 'Rejected',
        };
    }
}
