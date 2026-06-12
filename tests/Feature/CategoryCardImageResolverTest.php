<?php

use App\Models\ServiceCategory;
use App\Services\Public\CategoryCardImageResolver;

it('resolves near-you category card images by category code', function () {
    $category = ServiceCategory::factory()->create([
        'code' => 'cat-home-nursing-services',
        'name' => 'Home Nursing Services',
    ]);

    $url = app(CategoryCardImageResolver::class)->urlFor($category);

    expect($url)->toContain('home-nursing-services.jpg');
});

it('prefers featured image when set on category', function () {
    $category = ServiceCategory::factory()->create([
        'code' => 'cat-custom',
        'featured_image' => 'categories/custom.jpg',
    ]);

    $url = app(CategoryCardImageResolver::class)->urlFor($category);

    expect($url)->toContain('storage/categories/custom.jpg');
});
