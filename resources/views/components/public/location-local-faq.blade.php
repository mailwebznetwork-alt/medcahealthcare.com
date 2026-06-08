@props([
    'pin',
    'title' => null,
])

@php
    use App\Models\PinCode;

    if (! $pin instanceof PinCode) {
        return;
    }

    $pin->loadMissing('locationFaqs');
    $title = $title ?? __('Local FAQ');
@endphp

@if ($pin->locationFaqs->isNotEmpty())
    <section {{ $attributes->merge(['class' => 'space-y-4']) }}>
        <h2 class="text-lg font-semibold text-slate-900 md:text-xl">{{ $title }}</h2>
        <dl class="space-y-3">
            @foreach ($pin->locationFaqs as $faq)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <dt class="font-semibold text-slate-900">{{ $faq->question }}</dt>
                    <dd class="mt-2 text-sm leading-relaxed text-slate-600">{{ $faq->answer }}</dd>
                </div>
            @endforeach
        </dl>
    </section>
@endif
