<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies HMAC-SHA256(raw body, secret) against X-Exotel-Signature (hex).
 */
class VerifyExotelWebhookSignature
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('exotel.enabled', false)) {
            abort(503, __('Exotel integration is disabled.'));
        }

        $secret = config('exotel.webhook_hmac_secret');
        if (! is_string($secret) || trim($secret) === '') {
            return $next($request);
        }

        $provided = $request->header('X-Exotel-Signature');
        if (! is_string($provided) || $provided === '') {
            abort(403, __('Missing Exotel signature.'));
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        if (! hash_equals($expected, $provided)) {
            abort(403, __('Invalid Exotel signature.'));
        }

        $request->attributes->set('exotel_webhook_signature_verified', true);

        return $next($request);
    }
}
