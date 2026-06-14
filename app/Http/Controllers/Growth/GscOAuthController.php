<?php

namespace App\Http\Controllers\Growth;

use App\Http\Controllers\Controller;
use App\Services\Growth\GoogleSearchConsoleCredentialStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GscOAuthController extends Controller
{
    private const OAUTH_SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

    public function connect(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canAccessIntegrationsAdmin(), 403);

        $store = app(GoogleSearchConsoleCredentialStore::class);
        abort_unless($store->isOAuthConnectable(), 503);

        $state = Str::random(40);
        $request->session()->put('gsc_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $store->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => self::OAUTH_SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request, GoogleSearchConsoleCredentialStore $store): RedirectResponse
    {
        abort_unless($request->user()?->canAccessIntegrationsAdmin(), 403);

        $expectedState = (string) $request->session()->pull('gsc_oauth_state', '');
        if ($expectedState === '' || ! hash_equals($expectedState, (string) $request->query('state', ''))) {
            return redirect()
                ->route('growth-center.gsc.index')
                ->withErrors(['gsc' => __('Google Search Console authorization failed (invalid state).')]);
        }

        if ($request->filled('error')) {
            return redirect()
                ->route('growth-center.gsc.index')
                ->withErrors(['gsc' => (string) $request->query('error_description', $request->query('error'))]);
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()
                ->route('growth-center.gsc.index')
                ->withErrors(['gsc' => __('Google did not return an authorization code.')]);
        }

        $response = Http::asForm()
            ->timeout(15)
            ->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $store->clientId(),
                'client_secret' => $store->clientSecret(),
                'redirect_uri' => $this->redirectUri(),
                'grant_type' => 'authorization_code',
            ]);

        if (! $response->successful()) {
            return redirect()
                ->route('growth-center.gsc.index')
                ->withErrors(['gsc' => __('Could not exchange Google authorization code.')]);
        }

        $refreshToken = (string) ($response->json('refresh_token') ?? '');
        if ($refreshToken === '') {
            return redirect()
                ->route('growth-center.gsc.index')
                ->withErrors(['gsc' => __('Google did not return a refresh token. Revoke prior access and try again with consent.')]);
        }

        $store->storeOAuthTokens([
            'refresh_token' => $refreshToken,
            'access_token' => $response->json('access_token'),
        ]);

        return redirect()
            ->route('growth-center.gsc.index')
            ->with('gsc_connected', true);
    }

    public function disconnect(Request $request, GoogleSearchConsoleCredentialStore $store): RedirectResponse
    {
        abort_unless($request->user()?->canAccessIntegrationsAdmin(), 403);

        $store->disconnect();

        return redirect()
            ->route('growth-center.gsc.index')
            ->with('gsc_disconnected', true);
    }

    private function redirectUri(): string
    {
        return (string) config('growth.google_search_console.redirect_uri', route('growth-center.gsc.oauth.callback'));
    }
}
