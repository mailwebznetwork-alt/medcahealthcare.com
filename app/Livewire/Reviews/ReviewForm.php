<?php

namespace App\Livewire\Reviews;

use App\Models\Review;
use App\Models\Service;
use App\Policies\ReviewPolicy;
use App\Services\UserLocationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

class ReviewForm extends Component
{
    use AuthorizesRequests;

    public int $serviceId;

    public int $rating = 5;

    public string $comment = '';

    public function mount(int $serviceId): void
    {
        $this->serviceId = $serviceId;
    }

    public function submit(): void
    {
        $service = Service::query()->findOrFail($this->serviceId);
        $user = auth()->user();
        abort_unless($user !== null, 403);
        abort_unless(app(ReviewPolicy::class)->create($user, $service), 403);

        $validated = $this->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        Review::query()->create([
            'user_id' => auth()->id(),
            'service_id' => $service->id,
            'rating' => (int) $validated['rating'],
            'comment' => trim($validated['comment']) !== '' ? trim($validated['comment']) : null,
            'pincode' => app(UserLocationService::class)->currentPincode(),
            'status' => Review::STATUS_PENDING,
        ]);

        $this->reset('comment');
        $this->rating = 5;

        session()->flash('review_status', __('Thank you — your review is pending moderation.'));
    }

    public function render(): View
    {
        $service = Service::query()->findOrFail($this->serviceId);
        $canReview = auth()->check() && app(ReviewPolicy::class)->create(auth()->user(), $service);

        return view('livewire.reviews.review-form', [
            'service' => $service,
            'canReview' => $canReview,
        ]);
    }
}
