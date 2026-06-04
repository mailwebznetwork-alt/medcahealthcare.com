<?php

namespace App\Services\Marketing\Tracking;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MarketingTrackingValidator
{
    /** @var list<string> */
    private const ALLOWED_EVENTS = [
        'cta_click',
        'whatsapp_click',
        'phone_click',
        'form_start',
        'form_submit',
        'download_click',
        'email_click',
        'gbp_website_visit',
        'gbp_call_click',
        'gbp_whatsapp_click',
    ];

    /**
     * @return array<string, mixed>
     */
    public function validate(Request $request): array
    {
        $data = $request->validate([
            'event_type' => ['required', 'string', 'max:64'],
            'page_path' => ['nullable', 'string', 'max:500'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:128'],
            'medium' => ['nullable', 'string', 'max:128'],
            'element_label' => ['nullable', 'string', 'max:255'],
            'button_name' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:32'],
            'destination_url' => ['nullable', 'string', 'max:500'],
            'session_fingerprint' => ['nullable', 'string', 'max:64'],
            'meta' => ['nullable', 'array'],
        ]);

        if (! in_array($data['event_type'], self::ALLOWED_EVENTS, true)) {
            throw ValidationException::withMessages(['event_type' => __('Invalid event type.')]);
        }

        foreach (['page_path', 'campaign', 'source', 'medium', 'element_label', 'destination_url'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = Str::of(strip_tags($data[$field]))->trim()->toString();
            }
        }

        if (isset($data['destination_url']) && $data['destination_url'] !== '' && ! $this->isValidDestinationUrl($data['destination_url'])) {
            throw ValidationException::withMessages(['destination_url' => __('Invalid destination URL.')]);
        }

        return $data;
    }

    private function isValidDestinationUrl(string $url): bool
    {
        $lower = strtolower($url);

        if (str_starts_with($lower, 'tel:')) {
            $digits = preg_replace('/\D+/', '', substr($url, 4));

            return $digits !== null && strlen($digits) >= 8 && strlen($digits) <= 15;
        }

        if (str_starts_with($lower, 'mailto:')) {
            return filter_var(substr($url, 7), FILTER_VALIDATE_EMAIL) !== false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public function isDuplicate(string $fingerprint, string $eventType): bool
    {
        if ($fingerprint === '') {
            return false;
        }

        $key = 'marketing.click.dedupe.'.$fingerprint.'.'.$eventType;

        if (Cache::has($key)) {
            return true;
        }

        Cache::put($key, true, config('marketing_automation.click_tracking.dedupe_seconds', 3));

        return false;
    }
}
