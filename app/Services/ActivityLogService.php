<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class ActivityLogService
{
    public function log(string $action, string $module, ?string $description = null): void
    {
        try {
            DB::table('activity_logs')->insert([
                'user_id' => auth()->id(),
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
        }
    }
}
