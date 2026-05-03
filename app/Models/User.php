<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\ModuleAccess;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'module_access'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'module_access' => 'array',
        ];
    }

    /**
     * Effective module grants (persisted map merged over defaults for missing keys).
     *
     * @return array<string, bool>
     */
    public function resolvedModuleAccess(): array
    {
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
     * Sidebar nodes: single links or grouped items (e.g. Operations → Job Portal).
     *
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
}
