<?php

namespace App\Services;

use App\Services\Notifications\AdminNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ActivityLogService
{
    public function __construct(
        private readonly ?AdminNotificationService $adminNotifications = null,
    ) {}

    public function log(string $action, string $module, ?string $description = null): void
    {
        $actorId = auth()->id();

        try {
            DB::table('activity_logs')->insert([
                'user_id' => $actorId,
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::critical('Activity log could not be written to the database; falling back to file log.', [
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'exception' => $e->getMessage(),
            ]);

            return;
        }

        try {
            ($this->adminNotifications ?? app(AdminNotificationService::class))
                ->fanOutFromActivity($action, $module, $description, $actorId);
        } catch (Throwable $e) {
            Log::warning('Admin notification fan-out skipped after activity log.', [
                'action' => $action,
                'module' => $module,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
