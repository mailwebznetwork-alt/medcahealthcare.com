<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Integrations\OutboundWebhookDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * External payment bridges POST JSON here with bearer SETTINGS_PAYMENT_INGEST_BEARER → fires payment.received webhooks.
 */
class PaymentNotificationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $expected = config('settings.payment_ingest_bearer');
        if (! is_string($expected) || trim($expected) === '') {
            abort(503, __('Payment ingest is not configured.'));
        }

        $token = $request->bearerToken();
        if (! is_string($token) || ! hash_equals($expected, $token)) {
            abort(403, __('Invalid bearer token.'));
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric'],
            'currency' => ['nullable', 'string', 'max:12'],
            'reference' => ['nullable', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:64'],
            'meta' => ['nullable', 'array'],
        ]);

        app(OutboundWebhookDispatcher::class)->dispatch('payment.received', [
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'INR',
            'reference' => $validated['reference'] ?? null,
            'provider' => $validated['provider'] ?? null,
            'meta' => $validated['meta'] ?? [],
        ]);

        return response()->json(['ok' => true]);
    }
}
