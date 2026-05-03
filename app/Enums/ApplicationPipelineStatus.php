<?php

namespace App\Enums;

enum ApplicationPipelineStatus: string
{
    case Applied = 'applied';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Selected = 'selected';
    case Joined = 'joined';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Applied => __('Applied'),
            self::Shortlisted => __('Shortlisted'),
            self::Interview => __('Interview'),
            self::Selected => __('Selected'),
            self::Joined => __('Joined'),
            self::Rejected => __('Rejected'),
        };
    }
}
