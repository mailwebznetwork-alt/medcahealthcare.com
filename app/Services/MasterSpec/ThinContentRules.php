<?php

namespace App\Services\MasterSpec;

use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceLocationPage;

final class ThinContentRules
{
    public const int SERVICE_MIN_WORDS = 40;

    public const int LOCATION_MIN_WORDS = 80;

    public function serviceWordCount(Service $service): int
    {
        return str_word_count((string) ($service->description ?? '').' '.(string) ($service->short_summary ?? ''));
    }

    public function isThinService(Service $service): bool
    {
        return $this->serviceWordCount($service) < self::SERVICE_MIN_WORDS;
    }

    public function locationWordCount(?Page $page): int
    {
        return str_word_count(strip_tags((string) ($page?->content ?? '')));
    }

    public function isThinLocation(ServiceLocationPage $row): bool
    {
        if (! $row->is_indexable) {
            return false;
        }

        return $this->locationWordCount($row->page) < self::LOCATION_MIN_WORDS;
    }
}
