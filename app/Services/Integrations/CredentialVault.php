<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Crypt;
use Throwable;

class CredentialVault
{
    /**
     * @param  array<string, mixed>  $credentials
     * @return array<string, mixed>
     */
    public function encrypt(array $credentials): array
    {
        $encrypted = [];

        foreach ($credentials as $key => $value) {
            if (is_array($value)) {
                $encrypted[$key] = $this->encrypt($value);

                continue;
            }

            if ($value === null || $value === '') {
                $encrypted[$key] = null;

                continue;
            }

            $encrypted[$key] = Crypt::encryptString((string) $value);
        }

        return $encrypted;
    }

    /**
     * @param  array<string, mixed>|null  $credentials
     * @return array<string, mixed>
     */
    public function decrypt(?array $credentials): array
    {
        if (! is_array($credentials)) {
            return [];
        }

        $decrypted = [];

        foreach ($credentials as $key => $value) {
            if (is_array($value)) {
                $decrypted[$key] = $this->decrypt($value);

                continue;
            }

            if ($value === null || $value === '') {
                $decrypted[$key] = null;

                continue;
            }

            try {
                $decrypted[$key] = Crypt::decryptString((string) $value);
            } catch (Throwable) {
                $decrypted[$key] = $value;
            }
        }

        return $decrypted;
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array<string, mixed>
     */
    public function mask(array $credentials): array
    {
        $masked = [];

        foreach ($credentials as $key => $value) {
            if (is_array($value)) {
                $masked[$key] = $this->mask($value);

                continue;
            }

            $text = (string) $value;
            if ($text === '') {
                $masked[$key] = null;

                continue;
            }

            $masked[$key] = str_repeat('*', max(strlen($text) - 4, 0)).substr($text, -4);
        }

        return $masked;
    }
}
