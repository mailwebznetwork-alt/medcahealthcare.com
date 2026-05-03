<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\VacancyVisibility;
use App\Enums\VacancyWorkflowStatus;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vacancy>
 */
class VacancyFactory extends Factory
{
    protected $model = Vacancy::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->jobTitle();

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('####'),
            'department' => fake()->randomElement(['Clinical', 'Operations', 'Technology']),
            'city' => 'Bangalore',
            'area' => 'Arekere',
            'pin_code' => '560076',
            'country_code' => 'IN',
            'employment_type' => EmploymentType::FullTime,
            'salary_min' => fake()->optional()->randomFloat(2, 800000, 1200000),
            'salary_max' => fake()->optional()->randomFloat(2, 1200000, 2400000),
            'salary_currency' => 'INR',
            'closing_date' => now()->addMonths(2),
            'summary' => fake()->paragraph(),
            'description' => fake()->paragraphs(3, true),
            'requirements' => fake()->paragraph(),
            'whatsapp_apply_url' => null,
            'seo_title' => null,
            'seo_description' => null,
            'focus_keywords' => null,
            'ai_context' => null,
            'schema_json' => null,
            'visibility' => VacancyVisibility::Public,
            'workflow_status' => VacancyWorkflowStatus::Draft,
            'is_active' => true,
            'sort_order' => 0,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'workflow_status' => VacancyWorkflowStatus::Published,
            'visibility' => VacancyVisibility::Public,
            'published_at' => now(),
        ]);
    }
}
