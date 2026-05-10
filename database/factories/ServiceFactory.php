<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucfirst(fake()->unique()->words(2, true));

        return [
            'title' => $title,
            'service_code' => Str::slug($title).'-'.fake()->unique()->numerify('#####'),
            'short_summary' => fake()->sentence(10),
            'description' => '<p>'.fake()->paragraphs(2, true).'</p>',
            'price_range' => null,
            'featured_image' => null,
            'icon' => null,
            'detail_page_id' => null,
            'gallery' => null,
            'image_alt' => null,
            'target_keywords' => null,
            'ai_keywords' => null,
            'quality_score' => fake()->numberBetween(60, 95),
            'is_active' => true,
            'is_featured' => false,
            'publish_status' => PublishStatus::Published,
            'visibility' => ServiceVisibility::Public,
            'sort_order' => 0,
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes): array => [
            'publish_status' => PublishStatus::Draft,
        ]);
    }

    public function withPriceRange(string $range = '₹500 – ₹2,000'): self
    {
        return $this->state(fn (array $attributes): array => [
            'price_range' => $range,
        ]);
    }
}
