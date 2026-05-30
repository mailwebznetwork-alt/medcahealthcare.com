<?php

namespace App\Policies;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;

class ReviewPolicy
{
    public function create(User $user, Service $service): bool
    {
        if (Review::query()->where('user_id', $user->id)->where('service_id', $service->id)->exists()) {
            return false;
        }

        return $this->userHasCompletedLeadForService($user, $service);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Review $review): bool
    {
        return $review->isApproved() || ($user !== null && $user->id === $review->user_id);
    }

    private function userHasCompletedLeadForService(User $user, Service $service): bool
    {
        $email = strtolower(trim((string) $user->email));
        $phone = Lead::normalizePhone((string) ($user->phone ?? ''));

        if ($email === '' && $phone === '') {
            return false;
        }

        return Lead::query()
            ->whereIn('status', [LeadStatus::Converted, LeadStatus::Closed])
            ->where(function ($query) use ($service): void {
                $query->where('service', $service->title)
                    ->orWhere('service', $service->service_code);
            })
            ->where(function ($query) use ($email, $phone): void {
                if ($email !== '') {
                    $query->whereRaw('lower(email) = ?', [$email]);
                }
                if ($phone !== '') {
                    $query->orWhere('phone_normalized', $phone);
                }
            })
            ->exists();
    }
}
