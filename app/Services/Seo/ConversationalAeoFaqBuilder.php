<?php

namespace App\Services\Seo;

use App\Models\PinCode;
use App\Models\PinCodeHospital;
use App\Models\Service;
use App\Models\ServiceFaq;
use Illuminate\Support\Str;

/**
 * Builds conversational FAQ entities from live service + pincode records.
 */
class ConversationalAeoFaqBuilder
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forService(Service $service): array
    {
        $service->loadMissing(['faqs', 'pincodes', 'seo']);
        $stored = $service->toFaqEntities();
        $generated = [];

        $title = $service->title;
        foreach ($service->pincodes->take(5) as $pin) {
            $area = $this->areaLabel($pin);
            $generated[] = $this->question(
                __('Do you provide :service in :area :pincode?', [
                    'service' => $title,
                    'area' => $area,
                    'pincode' => $pin->pincode,
                ]),
                $this->coverageAnswer($service, $pin)
            );
        }

        if (filled($service->ai_summary)) {
            $generated[] = $this->question(
                __('What does :service include from MarkOnMinds?', ['service' => $title]),
                (string) $service->ai_summary
            );
        }

        if ($service->faqs->isNotEmpty()) {
            foreach ($service->faqs->take(3) as $faq) {
                if ($this->isGenericFaq($faq)) {
                    continue;
                }
                $generated[] = $this->question((string) $faq->question, (string) $faq->answer);
            }
        }

        return $this->mergeUnique($stored, $generated);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forLocation(Service $service, PinCode $pin): array
    {
        $pin->loadMissing(['locationFaqs', 'hospitals', 'nearbyAreas', 'landmarks']);
        $service->loadMissing(['seo', 'faqs']);

        $stored = $pin->locationFaqs->map(fn ($faq) => $this->question($faq->question, $faq->answer))->all();
        $generated = [];

        $area = $this->areaLabel($pin);
        $title = $service->title;

        $generated[] = $this->question(
            __('Do you provide :service in :area :pincode?', [
                'service' => $title,
                'area' => $area,
                'pincode' => $pin->pincode,
            ]),
            $this->coverageAnswer($service, $pin)
        );

        foreach ($pin->hospitals->take(2) as $hospital) {
            $generated[] = $this->question(
                __('Can :service staff coordinate care near :hospital in :area?', [
                    'service' => $title,
                    'hospital' => $hospital->name,
                    'area' => $area,
                ]),
                $this->hospitalAnswer($service, $pin, $hospital)
            );
        }

        if (filled($pin->emergency_coverage_text)) {
            $generated[] = $this->question(
                __('Is emergency :service support available in :area?', [
                    'service' => $title,
                    'area' => $area,
                ]),
                (string) $pin->emergency_coverage_text
            );
        }

        if ($pin->nearbyAreas->isNotEmpty()) {
            $nearbyList = $pin->nearbyAreas->pluck('area_name')->implode(', ');
            $generated[] = $this->question(
                __('Which nearby areas does :service cover from :area?', [
                    'service' => $title,
                    'area' => $area,
                ]),
                __('Coverage extends to :areas around pincode :pincode.', [
                    'areas' => $nearbyList,
                    'pincode' => $pin->pincode,
                ])
            );
        }

        return $this->mergeUnique($stored, $generated);
    }

    private function areaLabel(PinCode $pin): string
    {
        return (string) ($pin->area_name ?: $pin->locality ?: $pin->city ?: $pin->pincode);
    }

    private function coverageAnswer(Service $service, PinCode $pin): string
    {
        if (filled($pin->coverage_text)) {
            return (string) $pin->coverage_text;
        }

        $summary = $service->seo?->meta_description ?: $service->short_summary;
        $area = $this->areaLabel($pin);

        return filled($summary)
            ? trim((string) $summary).' '.__('Available in :area (:pincode).', ['area' => $area, 'pincode' => $pin->pincode])
            : __('Yes — :service is available in :area (:pincode).', [
                'service' => $service->title,
                'area' => $area,
                'pincode' => $pin->pincode,
            ]);
    }

    private function hospitalAnswer(Service $service, PinCode $pin, PinCodeHospital $hospital): string
    {
        $area = $this->areaLabel($pin);
        $parts = [
            __('MarkOnMinds coordinates :service visits for families near :hospital in :area.', [
                'service' => $service->title,
                'hospital' => $hospital->name,
                'area' => $area,
            ]),
        ];
        if (filled($hospital->specialty)) {
            $parts[] = __('Facility focus: :specialty.', ['specialty' => $hospital->specialty]);
        }

        return implode(' ', $parts);
    }

    private function isGenericFaq(ServiceFaq $faq): bool
    {
        $q = Str::lower((string) $faq->question);

        return str_contains($q, 'lorem') || str_contains($q, 'sample question') || strlen($q) < 12;
    }

    /**
     * @return array<string, mixed>
     */
    private function question(string $name, string $answer): array
    {
        return [
            '@type' => 'Question',
            'name' => trim($name),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => trim($answer),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $primary
     * @param  list<array<string, mixed>>  $secondary
     * @return list<array<string, mixed>>
     */
    private function mergeUnique(array $primary, array $secondary): array
    {
        $seen = [];
        $out = [];
        foreach (array_merge($primary, $secondary) as $item) {
            $key = Str::lower((string) ($item['name'] ?? ''));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $item;
        }

        return $out;
    }
}
