<?php

use App\Enums\PageLayoutMode;
use App\Models\Block;
use App\Models\Page;
use App\Models\Vacancy;

it('serves the careers hub from the CMS page at /careers without /p/', function () {
    Block::query()->updateOrCreate(
        ['block_slug' => 'careers-open-roles'],
        [
            'block_name' => 'Careers open roles',
            'code' => '@foreach($vacancies as $job)<p>{{ $job->title }}</p>@endforeach',
            'is_active' => true,
        ]
    );

    Page::query()->updateOrCreate(
        ['slug' => 'careers'],
        [
            'title' => 'Careers',
            'content' => '{{block:careers-open-roles}}',
            'is_active' => true,
            'layout_mode' => PageLayoutMode::Canvas,
        ]
    );

    $vacancy = Vacancy::factory()->published()->create([
        'slug' => 'cms-careers-role',
        'title' => 'CMS Careers Role Title',
    ]);

    $this->get('/careers')
        ->assertSuccessful()
        ->assertSee('CMS Careers Role Title', false);

    $this->get('/p/careers')
        ->assertRedirect('/careers');
});

it('serves services hub at /services and redirects legacy /p/services', function () {
    Page::query()->updateOrCreate(
        ['slug' => 'services'],
        [
            'title' => 'Services',
            'content' => '{{block:hero-services}}',
            'is_active' => true,
            'layout_mode' => PageLayoutMode::Canvas,
        ]
    );

    $this->get('/services')
        ->assertSuccessful();

    $this->get('/p/services')
        ->assertRedirect('/services');
});

it('uses canvas layout without the default max-width main wrapper', function () {
    $page = Page::query()->updateOrCreate(
        ['slug' => 'careers'],
        [
            'title' => 'Careers',
            'content' => '<section class="w-full" data-test="careers-canvas">Canvas body</section>',
            'is_active' => true,
            'layout_mode' => PageLayoutMode::Canvas,
        ]
    );

    $response = $this->get('/careers')->assertSuccessful();

    expect($page->fresh()->usesCanvasLayout())->toBeTrue();
    $response->assertSee('data-test="careers-canvas"', false);

    preg_match(
        '/<main[^>]*id="main-content"[^>]*class="([^"]*)"/',
        $response->getContent(),
        $matches
    );

    expect($matches[1] ?? '')
        ->toContain('w-full')
        ->not->toContain('max-w-6xl');
});

it('exposes vacancies in block blade via render context on the careers page', function () {
    Page::query()->updateOrCreate(
        ['slug' => 'careers'],
        [
            'title' => 'Careers',
            'content' => '{{block:careers-custom-list}}',
            'is_active' => true,
            'layout_mode' => PageLayoutMode::Canvas,
        ]
    );

    Vacancy::factory()->published()->create([
        'slug' => 'blade-listed-role',
        'title' => 'Blade Listed Role',
    ]);

    Block::query()->updateOrCreate(
        ['block_slug' => 'careers-custom-list'],
        [
            'block_name' => 'Careers custom list',
            'code' => '<ul>@foreach($vacancies as $job)<li>{{ $job->title }}</li>@endforeach</ul>',
            'is_active' => true,
        ]
    );

    $this->get('/careers')
        ->assertSuccessful()
        ->assertSee('Blade Listed Role', false);
});

it('redirects internal careers job detail template away from direct /p/ access', function () {
    Page::query()->updateOrCreate(
        ['slug' => config('careers.job_detail_page_slug')],
        [
            'title' => 'Job detail layout',
            'content' => '{{block:careers-job-detail-layout}}',
            'is_active' => true,
            'layout_mode' => PageLayoutMode::Canvas,
        ]
    );

    $this->get('/p/'.config('careers.job_detail_page_slug'))
        ->assertRedirect('/careers');
});
