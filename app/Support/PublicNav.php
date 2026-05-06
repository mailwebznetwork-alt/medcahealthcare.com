<?php

namespace App\Support;

final class PublicNav
{
    /**
     * Compare nav href to the current request path (same app origin).
     */
    public static function isCurrent(string $href): bool
    {
        try {
            $resolved = url()->to($href);
        } catch (\Throwable) {
            return false;
        }

        $target = trim((string) (parse_url($resolved, PHP_URL_PATH) ?? '/'), '/');
        $current = trim(request()->path(), '/');

        return $target === $current;
    }
}
