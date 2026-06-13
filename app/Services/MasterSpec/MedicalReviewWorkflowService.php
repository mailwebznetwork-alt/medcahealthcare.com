<?php

namespace App\Services\MasterSpec;

use App\Enums\MedicalReviewStatus;
use App\Enums\VerificationStatus;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MedicalReviewWorkflowService
{
    public function submitForMedicalReview(Model $entity): void
    {
        $entity->forceFill([
            'medical_review_status' => MedicalReviewStatus::PendingMedical,
            'verification_status' => VerificationStatus::Pending,
        ])->save();
    }

    public function approve(Model $entity, User $reviewer): void
    {
        $entity->forceFill([
            'medical_review_status' => MedicalReviewStatus::Approved,
            'verification_status' => VerificationStatus::Verified,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();
    }

    public function reject(Model $entity, User $reviewer): void
    {
        $entity->forceFill([
            'medical_review_status' => MedicalReviewStatus::Rejected,
            'verification_status' => VerificationStatus::Unverified,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();
    }

    public function canReview(User $user): bool
    {
        $role = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;

        return in_array($role, ['super_admin', 'admin', 'medical_reviewer'], true);
    }

    /**
     * @return class-string<Model>|null
     */
    public function resolveModel(string $type, int $id): ?Model
    {
        return match ($type) {
            'service' => Service::query()->find($id),
            'category' => ServiceCategory::query()->find($id),
            'sub_service' => SubService::query()->find($id),
            default => null,
        };
    }
}
