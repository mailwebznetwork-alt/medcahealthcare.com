<?php

namespace App\Policies;

use App\Models\ImportApprovalRequest;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Import\ImportApprovalService;

class ImportApprovalPolicy
{
    /**
     * @var list<string>
     */
    private const APPROVER_ROLES = ['super_admin', 'admin', 'manager', 'medical_reviewer'];

    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS) && $this->hasApproverRole($user);
    }

    public function view(User $user, ImportApprovalRequest $request): bool
    {
        return $this->viewAny($user);
    }

    public function approve(User $user, ImportApprovalRequest $request): bool
    {
        return $user->hasModuleAccess(ModuleAccess::OPERATIONS)
            && app(ImportApprovalService::class)->canApprove($user, $request);
    }

    public function reject(User $user, ImportApprovalRequest $request): bool
    {
        return $this->approve($user, $request);
    }

    public function submit(User $user): bool
    {
        if (! $user->hasModuleAccess(ModuleAccess::OPERATIONS)) {
            return false;
        }

        $role = $this->normalizedRole($user);

        return ! in_array($role, ['viewer', 'medical_reviewer'], true);
    }

    private function hasApproverRole(User $user): bool
    {
        return in_array($this->normalizedRole($user), self::APPROVER_ROLES, true);
    }

    private function normalizedRole(User $user): string
    {
        $role = $user->role instanceof \BackedEnum ? $user->role->value : (string) ($user->role ?? '');

        return strtolower(trim($role));
    }
}
