<?php

namespace App\Services\Notifications;

use App\Models\AdminNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminNotificationService
{
    public function __construct(
        private readonly AdminNotificationPresenter $presenter,
        private readonly AdminNotificationRecipientResolver $recipientResolver,
    ) {}

    public function fanOutFromActivity(string $action, string $module, ?string $description, ?int $actorUserId): void
    {
        if (! $this->presenter->shouldNotify($action, $module)) {
            return;
        }

        $payload = $this->presenter->present($action, $module, $description, $actorUserId);
        $recipientIds = $this->recipientResolver->resolve($actorUserId, $action, $module);

        if ($recipientIds->isEmpty()) {
            return;
        }

        $timestamp = now();
        $rows = [];

        foreach ($recipientIds as $recipientId) {
            $rows[] = [
                'recipient_user_id' => $recipientId,
                'module' => $payload['module'],
                'action' => $payload['action'],
                'entity_type' => $payload['entity_type'],
                'title' => $payload['title'],
                'body' => $payload['body'],
                'url' => $payload['url'],
                'actor_user_id' => $actorUserId,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        try {
            AdminNotification::query()->insert($rows);
        } catch (Throwable $e) {
            Log::warning('Admin notification fan-out failed.', [
                'action' => $action,
                'module' => $module,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function unreadCountFor(int $userId): int
    {
        return AdminNotification::query()
            ->forRecipient($userId)
            ->unread()
            ->count();
    }

    public function markAllReadFor(int $userId): int
    {
        return AdminNotification::query()
            ->forRecipient($userId)
            ->unread()
            ->update(['read_at' => now()]);
    }
}
