<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Services\Notifications\AdminNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function __construct(
        private readonly AdminNotificationService $notificationService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AdminNotification::class);

        $filter = (string) $request->query('filter', 'all');

        $notifications = AdminNotification::query()
            ->forRecipient((int) $request->user()->id)
            ->with('actor:id,name')
            ->when($filter === 'unread', fn ($query) => $query->unread())
            ->orderByDesc('created_at')
            ->paginate((int) config('notifications.per_page', 25))
            ->withQueryString();

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'unreadCount' => $this->notificationService->unreadCountFor((int) $request->user()->id),
        ]);
    }

    public function markRead(Request $request, AdminNotification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);

        $notification->markRead();

        if ($request->isMethod('get') && filled($notification->url)) {
            return redirect()->to((string) $notification->url);
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', AdminNotification::class);

        $this->notificationService->markAllReadFor((int) $request->user()->id);

        return back()->with('status', 'notifications-read');
    }
}
