<?php

namespace Database\Factories;

use App\Models\Block;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Block>
 */
class BlockFactory extends Factory
{
    protected $model = Block::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $slug = Str::slug($name).'-'.fake()->unique()->numerify('#####');

        return [
            'uuid' => (string) Str::uuid(),
            'block_name' => Str::title($name),
            'block_slug' => $slug,
            'description' => fake()->sentence(8),
            'block_type' => fake()->randomElement(['Hero', 'Text', 'CTA', 'Service Grid', 'Sections', 'Custom']),
            'code' => '<p>'.fake()->paragraph().'</p>',
            'schema_json' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
