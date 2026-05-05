<?php

namespace App\Services\Growth;

use App\Models\Intercept;
use Illuminate\Support\Facades\Schema;

class WarRoomService
{
    public function getDashboard(): array
    {
        if (! Schema::hasTable('intercepts')) {
            return [
                'active_intercepts' => 0,
                'completed_intercepts' => 0,
                'high_priority_count' => 0,
                'intercepts' => collect(),
            ];
        }

        return [
            'active_intercepts' => Intercept::query()->where('status', 'active')->count(),
            'completed_intercepts' => Intercept::query()->where('status', 'completed')->count(),
            'high_priority_count' => Intercept::query()->where('priority', 3)->count(),
            'intercepts' => Intercept::query()
                ->with('competitor:id,name')
                ->latest('id')
                ->limit(100)
                ->get(),
        ];
    }

    public function createIntercept(array $data): Intercept
    {
        return Intercept::query()->create($data);
    }

    public function updateStatus(int $id, array $data): ?Intercept
    {
        $intercept = Intercept::query()->find($id);
        if (! $intercept instanceof Intercept) {
            return null;
        }

        $intercept->fill($data)->save();

        return $intercept;
    }
}
