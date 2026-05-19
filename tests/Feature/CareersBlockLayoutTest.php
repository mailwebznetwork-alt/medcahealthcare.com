<?php

use App\Enums\PageLayoutMode;
use App\Models\Block;
use App\Models\Page;
use App\Models\Vacancy;
use App\Services\ContentParser;

it('renders job-portal module tokens as empty so blocks control listing layout', function () {
    Vacancy::factory()->published()->create([
        'title' => 'Module Token Should Not Render Title',
    ]);

    $html = ContentParser::parse('{{module:job-portal}}');

    expect($html)->toBe('');
});

it('serves /careers/{slug} via the shared CMS detail page with $vacancy in blocks', function () {
    Block::query()->updateOrCreate(
        ['block_slug' => 'careers-job-detail-test'],
        [
            'block_name' => 'Job detail test',
            'code' => '<h1 data-test="job-title">{{ $vacancy->title }}</h1>',
            'is_active' => true,
        ]
    );

    Page::query()->updateOrCreate(
        ['slug' => 'careers-job-detail'],
        [
            'title' => 'Job detail layout',
            'content' => '{{block:careers-job-detail-test}}',
            'is_active' => true,
            'layout_mode' => PageLayoutMode::Canvas,
        ]
    );

    $vacancy = Vacancy::factory()->published()->create([
        'slug' => 'custom-layout-role',
        'title' => 'Custom Layout Role Title',
    ]);

    $this->get(route('careers.show', ['slug' => $vacancy->slug]))
        ->assertSuccessful()
        ->assertSee('data-test="job-title"', false)
        ->assertSee('Custom Layout Role Title', false);
});

it('uses a vacancy-specific detail page when detail_page_id is set', function () {
    $page = Page::factory()->create([
        'slug' => 'vacancy-specific-layout',
        'content' => '<p data-test="vacancy-specific">{{ $vacancy->title }}</p>',
        'is_active' => true,
        'layout_mode' => PageLayoutMode::Canvas,
    ]);

    $vacancy = Vacancy::factory()->published()->create([
        'slug' => 'per-vacancy-layout',
        'title' => 'Per Vacancy Layout Title',
        'detail_page_id' => $page->id,
    ]);

    $this->get(route('careers.show', ['slug' => $vacancy->slug]))
        ->assertSuccessful()
        ->assertSee('data-test="vacancy-specific"', false)
        ->assertSee('Per Vacancy Layout Title', false);
});

it('shows WhatsApp apply on job detail when site WhatsApp is configured', function () {
    config(['medca.whatsapp_url' => 'https://wa.me/919999999999']);

    Block::query()->updateOrCreate(
        ['block_slug' => 'careers-job-wa-test'],
        [
            'block_name' => 'Job WA test',
            'code' => '@include(\'careers.partials.apply-panel\', [\'vacancy\' => $vacancy])',
            'is_active' => true,
        ]
    );

    Page::query()->updateOrCreate(
        ['slug' => 'careers-job-detail'],
        [
            'title' => 'Job detail layout',
            'content' => '{{block:careers-job-wa-test}}',
            'is_active' => true,
            'layout_mode' => PageLayoutMode::Canvas,
        ]
    );

    $vacancy = Vacancy::factory()->published()->create([
        'slug' => 'whatsapp-apply-role',
        'title' => 'WhatsApp Apply Role',
        'whatsapp_apply_url' => null,
    ]);

    $this->get(route('careers.show', ['slug' => $vacancy->slug]))
        ->assertSuccessful()
        ->assertSee(__('Apply on WhatsApp'), false)
        ->assertSee('wa.me/919999999999', false);
});

it('includes the apply form partial when referenced from a job detail block', function () {
    Block::query()->updateOrCreate(
        ['block_slug' => 'careers-job-apply-test'],
        [
            'block_name' => 'Job apply test',
            'code' => '@include(\'careers.partials.apply-form\', [\'vacancy\' => $vacancy])',
            'is_active' => true,
        ]
    );

    Page::query()->updateOrCreate(
        ['slug' => 'careers-job-detail'],
        [
            'title' => 'Job detail layout',
            'content' => '{{block:careers-job-apply-test}}',
            'is_active' => true,
            'layout_mode' => PageLayoutMode::Canvas,
        ]
    );

    $vacancy = Vacancy::factory()->published()->create([
        'slug' => 'apply-form-role',
        'title' => 'Apply Form Role',
    ]);

    $this->get(route('careers.show', ['slug' => $vacancy->slug]))
        ->assertSuccessful()
        ->assertSee('name="full_name"', false)
        ->assertSee(route('careers.apply', ['slug' => $vacancy->slug]), false);
});

it('renders block code with empty vacancies when no page context is set', function () {
    $html = ContentParser::renderBlockCode(
        '<ul>@foreach($vacancies as $job)<li>{{ $job->title }}</li>@endforeach</ul>'
    );

    expect($html)->toBe('<ul></ul>');
});
