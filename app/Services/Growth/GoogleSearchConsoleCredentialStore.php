<?php

namespace App\Services\Growth;

use App\Models\Integration;
use App\Services\Integrations\CredentialVault;
use Illuminate\Support\Facades\Cache;

class GoogleSearchConsoleCredentialStore
{
    public function __construct(
        private readonly CredentialVault $credentialVault,
    ) {}

    public function integration(): ?Integration
    {
        return Integration::query()->where('name', 'google_search_console')->first();
    }

    public function refreshToken(): ?string
    {
        $fromConfig = config('growth.google_search_console.refresh_token');
        if (is_string($fromConfig) && trim($fromConfig) !== '') {
            return trim($fromConfig);
        }

        $integration = $this->integration();
        if ($integration === null) {
            return null;
        }

        $credentials = $this->credentialVault->decrypt($integration->credentials);
        $token = $credentials['refresh_token'] ?? null;

        return is_string($token) && trim($token) !== '' ? trim($token) : null;
    }

    public function clientId(): ?string
    {
        return $this->credential('client_id', 'growth.google_search_console.client_id');
    }

    public function clientSecret(): ?string
    {
        return $this->credential('client_secret', 'growth.google_search_console.client_secret');
    }

    public function isOAuthConnectable(): bool
    {
        return filled($this->clientId()) && filled($this->clientSecret());
    }

    /**
     * @param  array{refresh_token: string, access_token?: string|null}  $tokens
     */
    public function storeOAuthTokens(array $tokens): void
    {
        $refreshToken = trim((string) ($tokens['refresh_token'] ?? ''));
        if ($refreshToken === '') {
            throw new \InvalidArgumentException('Refresh token is required.');
        }

        Integration::query()->updateOrCreate(
            ['name' => 'google_search_console'],
            [
                'type' => 'google',
                'is_enabled' => true,
                'credentials' => $this->credentialVault->encrypt([
                    'client_id' => (string) $this->clientId(),
                    'client_secret' => (string) $this->clientSecret(),
                    'refresh_token' => $refreshToken,
                    'site_url' => (string) config('growth.google_search_console.site_url', config('app.url')),
                ]),
                'last_used_at' => now(),
            ]
        );

        Cache::forget('growth.gsc.oauth_access_token');
    }

    public function disconnect(): void
    {
        $integration = $this->integration();
        if ($integration !== null) {
            $integration->delete();
        }

        Cache::forget('growth.gsc.oauth_access_token');
    }

    public function connectedViaIntegration(): bool
    {
        return $this->integration() !== null && filled($this->refreshToken());
    }

    private function credential(string $integrationKey, string $configKey): ?string
    {
        $integration = $this->integration();
        if ($integration !== null) {
            $credentials = $this->credentialVault->decrypt($integration->credentials);
            $value = $credentials[$integrationKey] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        $value = config($configKey);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
