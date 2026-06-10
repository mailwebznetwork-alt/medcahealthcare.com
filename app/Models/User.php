<?php

namespace App\Models;

use App\ModuleAccess;
use App\Support\AdminNavigation;
use App\Support\RootAccount;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'pincode', 'profile_image_path', 'role_label'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
     * Defense-in-depth gate for backend access (custom admin — Filament is not used).
     * Route middleware (`auth`, `active`, `module:*`, `role:*`) remains authoritative.
     */
    public function canAccessPanel(string $panel = 'admin'): bool
    {
        if ($panel !== 'admin') {
            return false;
        }

        if (! $this->is_active) {
            return false;
        }

        $role = strtolower(trim((string) ($this->role ?? '')));

        if ($role === '' && $this->isRootSuperAdmin()) {
            $role = 'super_admin';
        }

        return in_array($role, ['viewer', 'editor', 'manager', 'admin', 'super_admin'], true);
    }

    /**
     * Strict gate for integration admin API routes (admin + super_admin only).
     */
    public function canAccessIntegrationsAdmin(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $role = strtolower(trim((string) ($this->role ?? '')));

        return in_array($role, ['admin', 'super_admin'], true);
    }

    public function canBypassArchitectSaveConstraints(): bool
    {
        return \App\Support\ArchitectSaveBypass::eligible($this);
    }

    /**
     * Root super-admin bypasses email verification (operational continuity).
     */
    public function hasVerifiedEmail(): bool
    {
        if ($this->isRootSuperAdmin()) {
            return true;
        }

        return $this->email_verified_at !== null;
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
     * Whether this user may define or change dynamic module field schemas.
     */
    public function canManageDynamicModuleSchema(): bool
    {
        if ($this->isRootSuperAdmin()) {
            return true;
        }

        $names = config('module_builder.schema_manager_names', []);

        return $names !== []
            && in_array(strtolower(trim((string) $this->name)), $names, true);
    }

    /**
     * Exclude profile read-only identities (and optionally the root account) from the directory.
     *
     * @param  Builder<User>  $query
     */
    public function scopeVisibleInUserManagementDirectory(Builder $query): void
    {
        $table = $query->getModel()->getTable();

        if (config('user_management.hide_root_account_in_directory', true)) {
            $rootEmail = strtolower(trim((string) config('root_account.email', '')));
            if ($rootEmail !== '') {
                $query->whereRaw("lower({$table}.email) != ?", [$rootEmail]);
            }
        }

        $emails = config('user_management.profile_readonly_emails', []);
        $names = config('user_management.profile_readonly_names', []);

        if ($emails === [] && $names === []) {
            return;
        }

        self::applyUserManagementDirectoryReadonlyExclusions($query, $emails, $names, $table);
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
     * Null {@see $module_access} denies all modules except {@see role} super_admin (authority preserved).
     *
     * @return array<string, bool>
     */
    public function resolvedModuleAccess(): array
    {
        if ($this->isRootSuperAdmin()) {
            return ModuleAccess::defaultGrants();
        }

        if ($this->module_access === null) {
            if (strtolower((string) ($this->role ?? '')) === 'super_admin') {
                return ModuleAccess::defaultGrants();
            }

            return array_fill_keys(ModuleAccess::keys(), false);
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
        $navigation = ModuleAccess::navigation();
        $supplemental = AdminNavigation::supplementalTopLevel();

        foreach (AdminNavigation::sidebarOrder() as $navKey) {
            $accessKey = AdminNavigation::accessModuleKey($navKey);
            if (! $this->hasModuleAccess($accessKey)) {
                continue;
            }

            if (isset($supplemental[$navKey])) {
                $meta = $supplemental[$navKey];
                $out[] = [
                    'type' => 'link',
                    'key' => $navKey,
                    'label' => $meta['label'],
                    'icon' => $meta['icon'],
                    'route' => $meta['route'],
                ];

                continue;
            }

            if (! isset($navigation[$navKey])) {
                continue;
            }

            $meta = $navigation[$navKey];
            $children = $meta['children'] ?? [];

            if ($children !== []) {
                $out[] = [
                    'type' => 'group',
                    'key' => $navKey,
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
                    'key' => $navKey,
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
