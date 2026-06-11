<?php

use App\Models\User;
use App\ModuleAccess;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()
            ->whereNotNull('module_access')
            ->eachById(function (User $user): void {
                $access = is_array($user->module_access) ? $user->module_access : [];

                if (! array_key_exists(ModuleAccess::SYSTEM, $access) && ($access[ModuleAccess::SETTINGS] ?? false)) {
                    $access[ModuleAccess::SYSTEM] = true;
                    $user->forceFill(['module_access' => $access])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        User::query()
            ->whereNotNull('module_access')
            ->eachById(function (User $user): void {
                $access = is_array($user->module_access) ? $user->module_access : [];

                if (array_key_exists(ModuleAccess::SYSTEM, $access)) {
                    unset($access[ModuleAccess::SYSTEM]);
                    $user->forceFill(['module_access' => $access])->saveQuietly();
                }
            });
    }
};
