<?php

namespace App\Models\Concerns;

use App\Enums\MedicalReviewStatus;
use App\Enums\VerificationStatus;

trait HasMasterSpecContentFields
{
    /**
     * @return array<string, string>
     */
    protected function masterSpecContentFieldCasts(): array
    {
        return [
            'key_takeaways' => 'array',
            'activities_included' => 'array',
            'medical_review_status' => MedicalReviewStatus::class,
            'verification_status' => VerificationStatus::class,
            'reviewed_at' => 'datetime',
            'reviewed_by' => 'integer',
        ];
    }

    /**
     * @return list<string>
     */
    protected static function masterSpecFillableFields(): array
    {
        return [
            'quick_answer',
            'why_medca',
            'key_takeaways',
            'activities_included',
            'medical_review_status',
            'reviewed_by',
            'reviewed_at',
            'verification_status',
            'featured_video_url',
            'featured_video_title',
            'featured_video_description',
        ];
    }
}
