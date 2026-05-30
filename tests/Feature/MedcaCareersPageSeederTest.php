<?php

use App\Models\Block;
use App\Models\Page;
use App\Models\SiteNavigationItem;
use App\Models\Vacancy;
use Database\Seeders\MedcaCareersPageSeeder;

beforeEach(function (): void {
    $this->seed(MedcaCareersPageSeeder::class);
});

it('seeds careers hub blocks and page without deprecated modules', function () {
    $page = Page::query()->where('slug', 'careers')->firstOrFail();

    expect($page->content)
        ->toContain('{{block:hero-careers}}')
        ->toContain('{{block:careers-open-roles}}')
        ->not->toContain('{{module:');

    expect(Block::query()->where('block_slug', 'careers-open-roles')->exists())->toBeTrue();
    expect(Block::query()->where('block_slug', 'careers-job-detail-layout')->exists())->toBeTrue();
});

it('renders careers hub with hero and published vacancies', function () {
    Vacancy::factory()->published()->create([
        'slug' => 'seeded-careers-role',
        'title' => 'Seeded Careers Role Title',
    ]);

    $this->get('/careers')
        ->assertSuccessful()
        ->assertSee('Careers at Medca Health Care', false)
        ->assertSee('Seeded Careers Role Title', false)
        ->assertSee('data-job-card', false);
});

it('adds careers to header navigation when missing', function () {
    $page = Page::query()->where('slug', 'careers')->firstOrFail();

    expect(
        SiteNavigationItem::query()
            ->where('zone', SiteNavigationItem::ZONE_HEADER)
            ->where('page_id', $page->id)
            ->exists()
    )->toBeTrue();
});
