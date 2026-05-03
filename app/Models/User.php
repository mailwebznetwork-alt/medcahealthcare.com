<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\ModuleAccess;
use App\Support\RootAccount;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'phone', 'password', 'profile_image_path', 'role_label', 'module_access', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'module_access' => 'array',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if (RootAccount::isRootUser($user)) {
                $user->is_active = true;
                $user->module_access = ModuleAccess::defaultGrants();
            }
        });
    }

    public function isRootSuperAdmin(): bool
    {
        return RootAccount::isRootUser($this);
    }

    /**
     * Whether this account is excluded from edit / activate / delete in User Management.
     */
    public function isProfileReadOnlyInUserManagement(): bool
    {
        if ($this->isRootSuperAdmin()) {
            return false;
        }

        $emails = config('user_management.profile_readonly_emails', []);
        if ($emails !== [] && in_array(strtolower((string) $this->email), $emails, true)) {
            return true;
        }

        $names = config('user_management.profile_readonly_names', []);
        if ($names !== [] && in_array(strtolower(trim((string) $this->name)), $names, true)) {
            return true;
        }

        return false;
    }

    /**
     * Exclude profile read-only identities from the User Management user list (root always kept).
     *
     * @param  Builder<User>  $query
     */
    public function scopeVisibleInUserManagementDirectory(Builder $query): void
    {
        $emails = config('user_management.profile_readonly_emails', []);
        $names = config('user_management.profile_readonly_names', []);

        if ($emails === [] && $names === []) {
            return;
        }

        $table = $query->getModel()->getTable();
        $rootEmail = strtolower(trim((string) config('root_account.email', '')));

        $query->where(function (Builder $outer) use ($emails, $names, $rootEmail, $table): void {
            if ($rootEmail !== '') {
                $outer->whereRaw("lower({$table}.email) = ?", [$rootEmail])
                    ->orWhere(function (Builder $inner) use ($emails, $names, $table): void {
                        self::applyUserManagementDirectoryReadonlyExclusions($inner, $emails, $names, $table);
                    });
            } else {
                self::applyUserManagementDirectoryReadonlyExclusions($outer, $emails, $names, $table);
            }
        });
    }

    /**
     * @param  Builder<User>  $query
     * @param  array<int, string>  $emails
     * @param  array<int, string>  $names
     */
    private static function applyUserManagementDirectoryReadonlyExclusions(Builder $query, array $emails, array $names, string $table): void
    {
        $parts = [];
        $bindings = [];

        if ($emails !== []) {
            $placeholders = implode(',', array_fill(0, count($emails), '?'));
            $parts[] = "lower({$table}.email) not in ({$placeholders})";
            $bindings = array_merge($bindings, $emails);
        }

        if ($names !== []) {
            $placeholders = implode(',', array_fill(0, count($names), '?'));
            $parts[] = "lower(trim({$table}.name)) not in ({$placeholders})";
            $bindings = array_merge($bindings, $names);
        }

        if ($parts !== []) {
            $query->whereRaw(implode(' and ', $parts), $bindings);
        }
    }

    /**
     * Effective module grants (persisted map merged over defaults for missing keys).
     * Root account always resolves to full module access at runtime.
     *
     * @return array<string, bool>
     */
    public function resolvedModuleAccess(): array
    {
        if ($this->isRootSuperAdmin()) {
            return ModuleAccess::defaultGrants();
        }

        if ($this->module_access === null) {
            return ModuleAccess::defaultGrants();
        }

        $base = array_fill_keys(ModuleAccess::keys(), false);
        $stored = is_array($this->module_access) ? $this->module_access : [];

        foreach ($stored as $key => $value) {
            $key = (string) $key;
            if (ModuleAccess::isValidKey($key)) {
                $base[$key] = (bool) $value;
            }
        }

        return $base;
    }

    public function hasModuleAccess(string $key): bool
    {
        if (! ModuleAccess::isValidKey($key)) {
            return false;
        }

        return (bool) ($this->resolvedModuleAccess()[$key] ?? false);
    }

    /**
     * @return list<array{type: 'link', key: string, label: string, icon: string, route: string}|array{type: 'group', key: string, label: string, icon: string, children: list<array{key: string, label: string, icon: string, route: string}>}>
     */
    public function visibleSidebarNodes(): array
    {
        $out = [];

        foreach (ModuleAccess::navigation() as $key => $meta) {
            if (! $this->hasModuleAccess($key)) {
                continue;
            }

            $children = $meta['children'] ?? [];

            if ($children !== []) {
                $out[] = [
                    'type' => 'group',
                    'key' => $key,
                    'label' => $meta['label'],
                    'icon' => $meta['icon'],
                    'children' => $children,
                ];

                continue;
            }

            $routeName = $meta['route'] ?? null;
            if (is_string($routeName) && $routeName !== '') {
                $out[] = [
                    'type' => 'link',
                    'key' => $key,
                    'label' => $meta['label'],
                    'icon' => $meta['icon'],
                    'route' => $routeName,
                ];
            }
        }

        return $out;
    }

    /**
     * Short label for directory tables (enabled modules only).
     */
    public function moduleAccessSummary(): string
    {
        $labels = [];
        foreach (ModuleAccess::labelsForForm() as $key => $meta) {
            if ($this->hasModuleAccess($key)) {
                $labels[] = (string) $meta['label'];
            }
        }

        if ($labels === []) {
            return __('None');
        }

        return implode(' · ', $labels);
    }

    /**
     * Lucide icon names (kebab-case for data-lucide) for enabled modules, in canonical sidebar order.
     *
     * @return list<string>
     */
    public function enabledModuleAccessIcons(): array
    {
        $icons = [];
        foreach (ModuleAccess::keys() as $key) {
            if (! $this->hasModuleAccess($key)) {
                continue;
            }
            $icon = ModuleAccess::navigation()[$key]['icon'] ?? null;
            if (is_string($icon) && $icon !== '') {
                $icons[] = $icon;
            }
        }

        return $icons;
    }

    public function profileImageUrl(): ?string
    {
        if ($this->profile_image_path === null || $this->profile_image_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->profile_image_path);
    }
}
