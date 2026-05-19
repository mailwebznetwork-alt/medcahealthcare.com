{{--
    Data-only careers listing for Site Architect pages.
    Style the parent block in the block editor — this module adds no layout CSS.
--}}
<div data-medca-careers-listing>
    @if ($vacancies->isEmpty())
        <p>{{ __('No open roles right now.') }}</p>
    @else
        <ul>
            @foreach ($vacancies as $vacancy)
                <li>
                    <a href="{{ route('careers.show', ['slug' => $vacancy->slug]) }}">{{ $vacancy->title }}</a>
                    @if ($vacancy->city || $vacancy->employment_type)
                        <span>
                            @if ($vacancy->city)
                                {{ $vacancy->city }}
                            @endif
                            @if ($vacancy->employment_type)
                                {{ $vacancy->city ? ' · ' : '' }}{{ $vacancy->employment_type->label() }}
                            @endif
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
