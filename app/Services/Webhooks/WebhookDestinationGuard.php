<?php

namespace App\Services\Webhooks;

use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Optional destination IP allowlist for outbound HTTPS targets.
 */
class WebhookDestinationGuard
{
    /**
     * @param  list<string>  $cidrs
     */
    public function isHostAllowed(string $url, array $cidrs): bool
    {
        if ($cidrs === []) {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        $ip = gethostbyname($host);
        if ($ip === $host && ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        foreach ($cidrs as $cidr) {
            $cidr = trim((string) $cidr);
            if ($cidr === '') {
                continue;
            }

            if (IpUtils::checkIp($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }
}
