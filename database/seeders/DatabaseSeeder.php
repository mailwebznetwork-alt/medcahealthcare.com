<?php

namespace Database\Seeders;

use App\Models\User;
use App\ModuleAccess;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $email = env('SEED_ADMIN_EMAIL');
        $password = env('SEED_ADMIN_PASSWORD');

        if (is_string($email) && $email !== '' && is_string($password) && $password !== '') {
            $existing = User::query()->where('email', $email)->first();

            if ($existing !== null) {
                $allowPasswordReset = filter_var(env('ALLOW_SEED_ADMIN_PASSWORD_RESET', false), FILTER_VALIDATE_BOOL);

                if (app()->environment('production') && ! $allowPasswordReset) {
                    $this->command?->warn('Skipped admin password reset in production (set ALLOW_SEED_ADMIN_PASSWORD_RESET=true to override).');

                    return;
                }

                $existing->forceFill([
                    'name' => (string) env('SEED_ADMIN_NAME', 'Admin'),
                    'password' => $password,
                    'email_verified_at' => now(),
                    'role' => 'super_admin',
                    'role_label' => 'Super Admin',
                    'is_active' => true,
                    'module_access' => ModuleAccess::defaultGrants(),
                ])->save();

                return;
            }

            $user = new User;
            $user->fill([
                'name' => (string) env('SEED_ADMIN_NAME', 'Admin'),
                'email' => $email,
            ]);
            $user->forceFill([
                'password' => $password,
                'email_verified_at' => now(),
                'role' => 'super_admin',
                'role_label' => 'Super Admin',
                'is_active' => true,
                'module_access' => ModuleAccess::defaultGrants(),
            ]);
            $user->save();

            return;
        }

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'module_access' => ModuleAccess::defaultGrants(),
        ]);
    }
}
