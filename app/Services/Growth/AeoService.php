<?php

namespace App\Services\Growth;

use App\Models\SeoAiSignal;

class AeoService
{
    public function saveSignals(array $data): SeoAiSignal
    {
        $signal = SeoAiSignal::query()->latest('id')->first();

        if (! $signal instanceof SeoAiSignal) {
            return SeoAiSignal::query()->create($data);
        }

        $signal->fill($data)->save();

        return $signal;
    }
}
