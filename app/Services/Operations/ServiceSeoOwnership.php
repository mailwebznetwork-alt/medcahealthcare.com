<?php

namespace App\Services\Operations;

use App\Models\Page;

/**
 * B4: Pages are canonical SEO owners when linked detail page has meta filled.
 */
final class ServiceSeoOwnership
{
    public static function pageSeoOverridesService(?Page $page): bool
    {
        if ($page === null) {
            return false;
        }

        return filled($page->meta_title)
            || filled($page->meta_description)
            || filled($page->h1);
    }
}
