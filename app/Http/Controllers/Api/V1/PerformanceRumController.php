<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Collects anonymous Core Web Vitals beacons (no paid analytics APIs).
 */
class PerformanceRumController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (! config('growth.rum.enabled', true)) {
            return response()->json(['accepted' => false], 204);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:32'],
            'value' => ['required', 'numeric'],
            'rating' => ['nullable', 'string', 'max:16'],
            'id' => ['nullable', 'string', 'max:64'],
            'navigationType' => ['nullable', 'string', 'max:32'],
            'path' => ['nullable', 'string', 'max:2048'],
        ]);

        Log::info('rum.web_vital', [
            'metric' => $validated['name'],
            'value' => round((float) $validated['value'], 3),
            'rating' => $validated['rating'] ?? null,
            'id' => $validated['id'] ?? null,
            'navigation_type' => $validated['navigationType'] ?? null,
            'path' => $validated['path'] ?? $request->headers->get('Referer'),
            'user_agent' => substr((string) $request->userAgent(), 0, 256),
        ]);

        return response()->json(['accepted' => true]);
    }
}
