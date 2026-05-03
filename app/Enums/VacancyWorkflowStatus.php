<?php

namespace App\Enums;

enum VacancyWorkflowStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Unpublished = 'unpublished';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Published => __('Published'),
            self::Unpublished => __('Unpublished'),
            self::Archived => __('Archived'),
        };
    }
}
