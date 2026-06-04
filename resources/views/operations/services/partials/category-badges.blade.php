@php
    /** @var \App\Models\Service $service */
    $categories = $service->relationLoaded('categories') ? $service->categories : collect();
@endphp
@if ($categories->isNotEmpty())
    <div class="flex flex-wrap gap-1">
        @foreach ($categories as $cat)
            <span class="rounded-mom-chrome border border-[rgba(197,160,89,0.2)] bg-[rgba(197,160,89,0.08)] px-2 py-0.5 text-[10px] font-medium text-mom-gold" title="{{ $cat->code }}">
                {{ $cat->name }}
            </span>
        @endforeach
    </div>
@else
    <span class="text-[var(--text-muted)]">—</span>
@endif
