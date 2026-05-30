<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\UserLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function storePincode(Request $request, UserLocationService $location): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'pincode' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $resolved = $location->setManualPincode($validated['pincode']);
        if ($resolved === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('We do not service that pincode yet. Try another Bangalore pincode.'),
                ], 422);
            }

            return back()->withErrors(['pincode' => __('We do not service that pincode yet.')]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'pincode' => $resolved,
                'message' => __('Location updated.'),
            ]);
        }

        return back()->with('status', __('Location set to pincode :pin.', ['pin' => $resolved]));
    }

    public function storeGeolocation(Request $request, UserLocationService $location): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $request->session()->put(config('location.geo_attempted_session_key'), true);

        $resolved = $location->detectFromCoordinates(
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        if ($resolved === null) {
            return response()->json([
                'message' => __('Could not map your coordinates to a serviceable pincode. Enter it manually.'),
            ], 422);
        }

        return response()->json([
            'pincode' => $resolved,
            'message' => __('Location detected.'),
        ]);
    }
}
