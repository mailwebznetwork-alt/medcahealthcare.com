<?php

namespace App\Livewire\Admin;

use App\Models\AdminNotification;
use App\Services\Notifications\AdminNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public int $unreadCount = 0;

    /** @var Collection<int, AdminNotification> */
    public Collection $recent;

    public function mount(AdminNotificationService $notificationService): void
    {
        $this->recent = collect();
        $this->refreshNotifications($notificationService);
    }

    public function toggleOpen(): void
    {
        $this->open = ! $this->open;
    }

    public function markRead(int $notificationId, AdminNotificationService $notificationService): void
    {
        $notification = AdminNotification::query()->find($notificationId);

        if ($notification === null) {
            return;
        }

        $this->authorize('update', $notification);
        $notification->markRead();
        $this->refreshNotifications($notificationService);
    }

    public function markAllRead(AdminNotificationService $notificationService): void
    {
        $this->authorize('viewAny', AdminNotification::class);
        $notificationService->markAllReadFor((int) auth()->id());
        $this->refreshNotifications($notificationService);
    }

    public function render(): View
    {
        return view('livewire.admin.notification-bell');
    }

    private function refreshNotifications(AdminNotificationService $notificationService): void
    {
        $userId = (int) auth()->id();
        $this->unreadCount = $notificationService->unreadCountFor($userId);

        $this->recent = AdminNotification::query()
            ->forRecipient($userId)
            ->with('actor:id,name')
            ->orderByDesc('created_at')
            ->limit((int) config('notifications.bell_limit', 12))
            ->get();
    }
}
