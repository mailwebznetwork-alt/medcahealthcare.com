<?php

namespace App\Services\Growth;

use App\Models\BusinessProfile;
use App\Models\Intercept;
use Illuminate\Support\Facades\Schema;

class WarRoomService
{
    public function getDashboard(): array
    {
        if (! Schema::hasTable('intercepts')) {
            return [
                'pending_intercepts' => 0,
                'in_progress_intercepts' => 0,
                'completed_intercepts' => 0,
                'high_priority_count' => 0,
                'intercepts' => collect(),
            ];
        }

        return [
            'pending_intercepts' => Intercept::query()->where('status', 'pending')->count(),
            'in_progress_intercepts' => Intercept::query()->where('status', 'in_progress')->count(),
            'completed_intercepts' => Intercept::query()->where('status', 'completed')->count(),
            'high_priority_count' => Intercept::query()->where('priority', 'high')->count(),
            'intercepts' => Intercept::query()
                ->with('competitor:id,name')
                ->latest('id')
                ->limit(100)
                ->get(),
        ];
    }

    public function createIntercept(array $data): Intercept
    {
        $profile = BusinessProfile::query()->firstOrCreate(
            ['website' => config('app.url')],
            [
                'name' => config('medca.brand_name', 'Karnataka Diagnostic Centre'),
                'email' => config('mail.from.address'),
            ]
        );

        return Intercept::query()->create([
            'business_profile_id' => $profile->id,
            'keyword' => $data['keyword'],
            'title' => $data['keyword'],
            'channel' => 'growth_war_room',
            'competitor_id' => $data['competitor_id'] ?? null,
            'gap_type' => $data['gap_type'],
            'action' => $data['action'],
            'priority' => $data['priority'],
            'status' => $data['status'] ?? 'pending',
        ]);
    }

    public function updateStatus(int $id, array $data): ?Intercept
    {
        $intercept = Intercept::query()->find($id);
        if (! $intercept instanceof Intercept) {
            return null;
        }

        $intercept->fill([
            'keyword' => $data['keyword'] ?? $intercept->keyword,
            'competitor_id' => $data['competitor_id'] ?? $intercept->competitor_id,
            'gap_type' => $data['gap_type'] ?? $intercept->gap_type,
            'action' => $data['action'] ?? $intercept->action,
            'priority' => $data['priority'] ?? $intercept->priority,
            'status' => $data['status'] ?? $intercept->status,
        ])->save();

        return $intercept;
    }
}
