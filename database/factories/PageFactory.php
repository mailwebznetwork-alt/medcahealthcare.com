<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = Str::slug(fake()->unique()->words(3, true)).'-'.fake()->unique()->numerify('#####');

        return [
            'uuid' => (string) Str::uuid(),
            'title' => fake()->sentence(3),
            'slug' => $slug,
            'content' => '',
            'is_active' => true,
        ];
    }
}
