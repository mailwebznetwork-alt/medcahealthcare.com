<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\SiteNavigationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteNavigationItem>
 */
class SiteNavigationItemFactory extends Factory
{
    protected $model = SiteNavigationItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'zone' => SiteNavigationItem::ZONE_HEADER,
            'page_id' => Page::factory(),
            'sort_order' => 0,
            'custom_label' => null,
        ];
    }

    public function header(): self
    {
        return $this->state(fn (array $attributes): array => ['zone' => SiteNavigationItem::ZONE_HEADER]);
    }

    public function footer(): self
    {
        return $this->state(fn (array $attributes): array => ['zone' => SiteNavigationItem::ZONE_FOOTER]);
    }
}
