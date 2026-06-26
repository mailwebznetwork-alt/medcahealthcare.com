<?php

namespace App\Jobs;

use App\Models\User;
use App\ModuleAccess;
use App\Services\MasterSpec\ContentHealthService;
use App\Services\Notifications\AdminNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyContentQualityAlertsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $trigger = 'scheduled',
    ) {}

    public function handle(ContentHealthService $health, AdminNotificationService $notifications): void
    {
        $report = $health->report();
        $thinServices = (int) ($report['thin_services'] ?? 0);
        $thinLocations = (int) ($report['thin_indexable_locations'] ?? 0);
        $pendingMedical = (int) ($report['pending_medical_review'] ?? 0);
        $missingQuick = (int) ($report['missing_quick_answer'] ?? 0);

        if ($thinServices === 0 && $thinLocations === 0 && $pendingMedical === 0 && $missingQuick === 0) {
            return;
        }

        $summary = sprintf(
            'Thin services: %d · Thin locations: %d · Missing quick answers: %d · Pending medical review: %d',
            $thinServices,
            $thinLocations,
            $missingQuick,
            $pendingMedical,
        );

        Log::info('Content quality alert', [
            'trigger' => $this->trigger,
            'summary' => $summary,
            'report' => $report,
        ]);

        $recipientIds = User::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (User $user): bool => $user->hasModuleAccess(ModuleAccess::OPERATIONS))
            ->pluck('id')
            ->all();

        if ($recipientIds === []) {
            return;
        }

        $notifications->notifyMany(
            $recipientIds,
            'content_quality_alert',
            __('Catalog content needs attention'),
            $summary,
            route('operations.content-health.index'),
        );
    }
}
