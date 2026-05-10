<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceFaq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceFaq>
 */
class ServiceFaqFactory extends Factory
{
    protected $model = ServiceFaq::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'question' => fake()->sentence().'?',
            'answer' => fake()->paragraph(),
        ];
    }
}
