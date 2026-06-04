<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SystemOverviewController extends Controller
{
    public function index(): View
    {
        return view('system.overview', $this->overviewPayload());
    }

    public function queue(): View
    {
        return view('system.queue', [
            'failedJobsCount' => $this->failedJobsCount(),
        ]);
    }

    public function scheduler(): View
    {
        return view('system.scheduler', [
            'scheduledTasks' => $this->scheduledTaskSummaries(),
        ]);
    }

    public function health(): View
    {
        return view('system.health', $this->overviewPayload());
    }

    /**
     * @return array<string, mixed>
     */
    private function overviewPayload(): array
    {
        return [
            'appName' => config('app.name'),
            'environment' => config('app.env'),
            'debug' => (bool) config('app.debug'),
            'queueConnection' => (string) config('queue.default'),
            'queueDriver' => config('queue.connections.'.config('queue.default').'.driver'),
            'scheduledTasks' => $this->scheduledTaskSummaries(),
            'databaseConnected' => $this->databaseConnected(),
            'failedJobsCount' => $this->failedJobsCount(),
        ];
    }

    /**
     * @return list<array{command: string, expression: string, description: string}>
     */
    private function scheduledTaskSummaries(): array
    {
        $tasks = [];

        try {
            foreach (Schedule::events() as $event) {
                $tasks[] = [
                    'command' => $event->command ?? $event->description ?? __('Scheduled callback'),
                    'expression' => $event->expression,
                    'description' => $event->description ?? '',
                ];
            }
        } catch (\Throwable) {
            $tasks[] = [
                'command' => __('Schedule registry unavailable in this context'),
                'expression' => '—',
                'description' => __('See routes/console.php for defined schedules.'),
            ];
        }

        if ($tasks === []) {
            $tasks[] = [
                'command' => 'app:sync-google-business-reviews',
                'expression' => '0 */4 * * *',
                'description' => __('Every four hours (routes/console.php)'),
            ];
            if (config('settings.schedule_database_backup')) {
                $tasks[] = [
                    'command' => 'mom:backup-database',
                    'expression' => '15 2 * * *',
                    'description' => __('Daily database backup'),
                ];
            }
            if (config('growth.schedule_ai_pulse_daily')) {
                $tasks[] = [
                    'command' => 'ai-pulse-rebuild',
                    'expression' => '33 3 * * *',
                    'description' => __('AI Pulse snapshot rebuild'),
                ];
            }
            if (config('marketing_automation.enabled', true)) {
                $tasks[] = [
                    'command' => 'marketing-analytics-aggregate',
                    'expression' => (string) config('marketing_automation.analytics.aggregate_daily_at', '01:15'),
                    'description' => __('Marketing analytics aggregate'),
                ];
            }
        }

        return $tasks;
    }

    private function databaseConnected(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function failedJobsCount(): ?int
    {
        try {
            if (! Schema::hasTable('failed_jobs')) {
                return null;
            }

            return (int) DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            return null;
        }
    }
}
