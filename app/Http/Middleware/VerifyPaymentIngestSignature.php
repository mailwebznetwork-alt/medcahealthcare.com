<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies HMAC-SHA256(raw body, secret) against X-Payment-Signature (hex).
 * When SETTINGS_PAYMENT_INGEST_HMAC_SECRET is empty, delegates to the controller
 * (legacy bearer-only ingest).
 */
class VerifyPaymentIngestSignature
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('settings.payment_ingest_hmac_secret');
        if (! is_string($secret) || trim($secret) === '') {
            return $next($request);
        }

        $provided = $request->header('X-Payment-Signature');
        if (! is_string($provided) || $provided === '') {
            abort(403, __('Missing payment signature.'));
        }

        $body = $request->getContent();
        $expected = hash_hmac('sha256', $body, $secret);

        if (! hash_equals($expected, $provided)) {
            abort(403, __('Invalid payment signature.'));
        }

        $request->attributes->set('payment_ingest_signature_verified', true);

        return $next($request);
    }
}
