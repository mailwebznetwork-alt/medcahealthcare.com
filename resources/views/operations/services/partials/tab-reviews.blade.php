@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Review> $serviceReviews */
    $serviceReviews = $serviceReviews ?? collect();
@endphp

<section class="mom-card p-6">
    <h3 class="mom-section-title mb-2">{{ __('Patient reviews') }}</h3>
    <p class="mom-subtext mb-6 max-w-3xl">
        {{ __('Moderate reviews submitted after a completed lead. Approved reviews appear in ratings and schema on the public service page.') }}
    </p>

    @if ($serviceReviews->isEmpty())
        <p class="mom-body-text text-[var(--text-muted)]">{{ __('No reviews for this service yet.') }}</p>
    @else
        <div class="overflow-x-auto">
            <table class="mom-table min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-[rgba(255,255,255,0.08)] text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">
                        <th class="px-3 py-2">{{ __('Reviewer') }}</th>
                        <th class="px-3 py-2">{{ __('Rating') }}</th>
                        <th class="px-3 py-2">{{ __('Comment') }}</th>
                        <th class="px-3 py-2">{{ __('Pincode') }}</th>
                        <th class="px-3 py-2">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[rgba(255,255,255,0.06)]">
                    @foreach ($serviceReviews as $index => $review)
                        <tr class="align-top">
                            <td class="px-3 py-3 text-[var(--text-secondary)]">
                                {{ $review->user?->name ?: $review->user?->email ?: __('Guest') }}
                                <input type="hidden" name="review_moderation[{{ $index }}][id]" value="{{ $review->id }}" />
                            </td>
                            <td class="px-3 py-3 font-mono text-[var(--text-primary)]">{{ $review->rating }}/5</td>
                            <td class="max-w-md px-3 py-3 text-[var(--text-secondary)]">{{ \Illuminate\Support\Str::limit($review->comment ?? '', 200) }}</td>
                            <td class="px-3 py-3 font-mono text-xs text-[var(--text-muted)]">{{ $review->pincode ?: '—' }}</td>
                            <td class="px-3 py-3">
                                <select
                                    name="review_moderation[{{ $index }}][status]"
                                    class="rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-2 py-1.5 text-sm text-[var(--text-primary)]"
                                >
                                    <option value="pending" @selected(old('review_moderation.'.$index.'.status', $review->status) === 'pending')>{{ __('Pending') }}</option>
                                    <option value="approved" @selected(old('review_moderation.'.$index.'.status', $review->status) === 'approved')>{{ __('Approved') }}</option>
                                    <option value="rejected" @selected(old('review_moderation.'.$index.'.status', $review->status) === 'rejected')>{{ __('Rejected') }}</option>
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
