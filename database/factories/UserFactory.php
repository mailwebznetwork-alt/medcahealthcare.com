<?php

namespace Database\Factories;

use App\Models\User;
use App\ModuleAccess;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => null,
            'profile_image_path' => null,
            'role_label' => null,
            'last_login_at' => null,
            'password' => static::$password ?? 'password',
            'role' => 'editor',
            'module_access' => ModuleAccess::defaultGrants(),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function newModel(array $attributes = []): User
    {
        $sensitiveKeys = [
            'password',
            'role',
            'role_label',
            'module_access',
            'is_active',
            'remember_token',
            'email_verified_at',
        ];

        $sensitive = [];
        foreach ($sensitiveKeys as $key) {
            if (array_key_exists($key, $attributes)) {
                $sensitive[$key] = $attributes[$key];
                unset($attributes[$key]);
            }
        }

        $model = new User;
        $model->fill($attributes);
        $model->forceFill($sensitive);

        return $model;
    }

    public function rootSuperAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => config('root_account.email', 'wdjerrie@markonminds.test'),
            'name' => 'MOMJERRIE',
            'role_label' => 'Root Super Admin',
            'role' => 'super_admin',
            'module_access' => ModuleAccess::defaultGrants(),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
