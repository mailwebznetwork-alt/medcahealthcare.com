<?php

namespace App\Services\Operations;

use App\Models\PinCode;
use App\Models\Service;

/**
 * Resolves location page copy from workbook templates (custom_fields) before config fallbacks.
 */
final class ServiceLocationTemplateResolver
{
    public function locationTitle(Service $service, PinCode $pin): string
    {
        return $this->template($service, $pin, 'location_h1_template')
            ?? $this->applyPattern(
                (string) config('services_master.location_page_title_pattern', '{service} in {area}'),
                $service,
                $pin
            );
    }

    public function locationH2(Service $service, PinCode $pin): ?string
    {
        return $this->template($service, $pin, 'location_h2_template');
    }

    public function locationH3(Service $service, PinCode $pin): ?string
    {
        return $this->template($service, $pin, 'location_h3_template');
    }

    public function localIntro(Service $service, PinCode $pin, string $configFallback): string
    {
        return $this->template($service, $pin, 'location_intro_template')
            ?? $configFallback;
    }

    public function localDescription(Service $service, PinCode $pin, ?string $configFallback = null): ?string
    {
        return $this->template($service, $pin, 'location_description_template') ?? $configFallback;
    }

    public function localMetaTitle(Service $service, PinCode $pin, string $configFallback): string
    {
        $resolved = $this->template($service, $pin, 'location_meta_title_template');

        return mb_substr($resolved ?? $configFallback, 0, 255);
    }

    public function localMetaDescription(Service $service, PinCode $pin, string $configFallback): string
    {
        $resolved = $this->template($service, $pin, 'location_meta_description_template');

        return mb_substr($resolved ?? $configFallback, 0, 320);
    }

    public function ctaHeading(Service $service, PinCode $pin): ?string
    {
        return $this->template($service, $pin, 'location_cta_heading');
    }

    public function ctaContent(Service $service, PinCode $pin): ?string
    {
        return $this->template($service, $pin, 'location_cta_content');
    }

    public function faqTemplate(Service $service, PinCode $pin): ?string
    {
        return $this->template($service, $pin, 'location_faq_template');
    }

    private function template(Service $service, PinCode $pin, string $key): ?string
    {
        $custom = is_array($service->custom_fields) ? $service->custom_fields : [];
        $value = $custom[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $this->applyPattern(trim($value), $service, $pin);
    }

    private function applyPattern(string $pattern, Service $service, PinCode $pin): string
    {
        $area = $pin->area_name ?: $pin->locality ?: $pin->city ?: $pin->pincode;
        $coverage = filled($pin->coverage_text) ? (string) $pin->coverage_text : $area;

        return str_replace(
            ['{service}', '{area}', '{city}', '{pincode}', '{coverage}'],
            [$service->title, $area, (string) ($pin->city ?? ''), $pin->pincode, $coverage],
            $pattern
        );
    }
}
