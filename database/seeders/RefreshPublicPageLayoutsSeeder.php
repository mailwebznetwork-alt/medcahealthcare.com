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
