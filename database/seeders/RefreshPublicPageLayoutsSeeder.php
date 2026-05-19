<?php

namespace Database\Seeders;

use App\Enums\PageLayoutMode;
use App\Models\Block;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Strips system-imposed service/careers layouts so blocks hold data tokens
 * and minimal markup for Site Architect styling.
 */
class RefreshPublicPageLayoutsSeeder extends Seeder
{
    public function run(): void
    {
        Block::query()->where('block_slug', 'sdfdfsdf')->update([
            'code' => "{{service:homenursing-services}}\n{{service:caregivers}}",
        ]);

        Block::query()->updateOrCreate(
            ['block_slug' => 'careers-open-roles'],
            [
                'block_name' => 'Careers — open roles (your layout)',
                'code' => <<<'BLADE'
{{-- $vacancies is injected on /careers. Replace with your carousel, cards, table, or grid. --}}
@if ($vacancies->isEmpty())
    <p>{{ __('No open roles right now.') }}</p>
@else
    @foreach ($vacancies as $vacancy)
        <article>
            <h2>
                <a href="{{ route('careers.show', ['slug' => $vacancy->slug]) }}">{{ $vacancy->title }}</a>
            </h2>
            <p>
                @if ($vacancy->city){{ $vacancy->city }}@endif
                @if ($vacancy->employment_type) · {{ $vacancy->employment_type->label() }}@endif
            </p>
        </article>
    @endforeach
@endif
BLADE,
                'is_active' => true,
            ]
        );

        $cta = Block::query()->where('block_slug', 'cta-services')->first();
        if ($cta !== null) {
            $cta->code = str_replace('/p/contact', '/contact', (string) $cta->code);
            $cta->save();
        }

        Block::query()->updateOrCreate(
            ['block_slug' => 'careers-job-detail'],
            [
                'block_name' => 'Careers — job detail (your layout)',
                'code' => <<<'BLADE'
{{-- $vacancy is injected on /careers/{slug}. Style hero, description, and apply section in this block. --}}
<article class="w-full" data-careers-job-detail>
    <header>
        <h1>{{ $vacancy->title }}</h1>
        <p>
            {{ $vacancy->employment_type->label() }}
            @if ($vacancy->city) · {{ $vacancy->city }}@endif
        </p>
    </header>
    @if ($vacancy->summary)
        <section><h2>{{ __('Overview') }}</h2><div>{{ $vacancy->summary }}</div></section>
    @endif
    @if ($vacancy->description)
        <section><h2>{{ __('Role description') }}</h2><div>{{ $vacancy->description }}</div></section>
    @endif
    @if ($vacancy->requirements)
        <section><h2>{{ __('Requirements') }}</h2><div>{{ $vacancy->requirements }}</div></section>
    @endif
    <section class="mc-job-detail-apply">
        @include('careers.partials.apply-panel', ['vacancy' => $vacancy])
    </section>
</article>
BLADE,
                'is_active' => true,
            ]
        );

        Page::query()->updateOrCreate(
            ['slug' => 'careers-job-detail'],
            [
                'title' => 'Careers job detail (layout template)',
                'content' => '{{block:careers-job-detail}}',
                'is_active' => true,
                'layout_mode' => PageLayoutMode::Canvas,
            ]
        );

        Page::query()->where('slug', 'careers')->update([
            'content' => '{{block:careers-open-roles}}',
            'layout_mode' => PageLayoutMode::Canvas,
        ]);

        Page::query()->where('slug', 'services')->update([
            'content' => "{{block:hero-services}}\n{{block:sdfdfsdf}}\n{{block:cta-services}}",
            'layout_mode' => PageLayoutMode::Canvas,
        ]);
    }
}
