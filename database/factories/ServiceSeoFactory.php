<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceSeo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceSeo>
 */
class ServiceSeoFactory extends Factory
{
    protected $model = ServiceSeo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'meta_title' => fake()->sentence(6),
            'meta_description' => fake()->sentence(20),
            'focus_keywords' => null,
            'h1' => null,
            'h2' => null,
            'h3' => null,
            'ai_context' => null,
            'search_intent' => null,
        ];
    }
}
