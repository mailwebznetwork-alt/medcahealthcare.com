<?php

namespace Database\Factories;

use App\Enums\ApplicationPipelineStatus;
use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vacancy_id' => Vacancy::factory(),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('9#########'),
            'pin_code' => '560076',
            'city' => 'Bangalore',
            'cover_message' => fake()->optional()->sentence(),
            'source' => fake()->randomElement(['web', 'whatsapp', 'referral']),
            'whatsapp_clicked_at' => null,
            'pipeline_status' => ApplicationPipelineStatus::Applied,
            'meta' => null,
        ];
    }
}
