<div class="mx-auto max-w-3xl space-y-6">
    <h2 class="text-lg font-semibold tracking-tight text-gray-900 dark:text-gray-100">
        {{ __('Open Positions') }}
    </h2>

    @if ($vacancies->isEmpty())
        <div class="rounded-lg border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('No open roles right now') }}</p>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Roles appear here after you publish a vacancy with public visibility (same listings as the careers page).') }}
            </p>
        </div>
    @else
        <ul class="space-y-4">
            @foreach ($vacancies as $vacancy)
                <li class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="font-medium text-gray-900 dark:text-gray-100">
                        <a href="{{ route('careers.show', ['slug' => $vacancy->slug]) }}" class="hover:underline">{{ $vacancy->title }}</a>
                    </p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        @if ($vacancy->city)
                            {{ $vacancy->city }}
                        @endif
                        @if ($vacancy->employment_type)
                            {{ $vacancy->city ? ' · ' : '' }}{{ $vacancy->employment_type->label() }}
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
</div>
