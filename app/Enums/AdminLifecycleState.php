<?php

namespace App\Enums;

/**
 * Admin intent lifecycle — governs what automated processes may do.
 *
 * ACTIVE: normal operation
 * DISABLED: excluded from generation/indexing
 * ARCHIVED: excluded from generation/indexing
 * DELETED_BY_ADMIN: permanently excluded from auto-heal and recreation
 * SYSTEM_MANAGED: eligible for downstream repair by sync processes
 */
enum AdminLifecycleState: string
{
    case Active = 'active';
    case Disabled = 'disabled';
    case Archived = 'archived';
    case DeletedByAdmin = 'deleted_by_admin';
    case SystemManaged = 'system_managed';

    public function allowsAutoHeal(): bool
    {
        return match ($this) {
            self::Active, self::SystemManaged => true,
            default => false,
        };
    }

    public function allowsRecreation(): bool
    {
        return match ($this) {
            self::Active, self::SystemManaged => true,
            default => false,
        };
    }

    public function excludedFromGeneration(): bool
    {
        return match ($this) {
            self::Disabled, self::Archived, self::DeletedByAdmin => true,
            default => false,
        };
    }
}
