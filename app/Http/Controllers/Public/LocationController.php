<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Discovery\ChangePincodeEngine;
use App\Services\Seo\LocalityContextResolver;
use App\Services\UserLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class LocationController extends Controller
{
    public function selectPincode(string $pincode, ChangePincodeEngine $pincodeEngine): RedirectResponse
    {
        $normalized = preg_replace('/\D/', '', $pincode) ?? '';
        abort_if(strlen($normalized) !== 6, 404);

        $result = $pincodeEngine->switch($normalized);

        $redirect = redirect()->to(url('/locations').'#near-you');

        if (! $result['success']) {
            return $redirect->withErrors(['pincode' => $result['message']]);
        }

        return $redirect->with('status', $result['message']);
    }

    public function storePincode(Request $request, ChangePincodeEngine $pincodeEngine): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'pincode' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $result = $pincodeEngine->switch($validated['pincode']);
        if (! $result['success']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $result['message'],
                ], 422);
            }

            return back()->withErrors(['pincode' => $result['message']]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'pincode' => $result['pincode'],
                'message' => $result['message'],
                'discovery' => $result['discovery'],
            ]);
        }

        return back()->with('status', $result['message']);
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
