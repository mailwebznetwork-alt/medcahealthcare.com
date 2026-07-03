<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\GlobalContentVariable;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical Medca brand copy for content completion (DB global vars → config fallbacks).
 */
final class BrandContentCatalog
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        static $cache = null;

        if (is_array($cache)) {
            return $cache;
        }

        $vars = Schema::hasTable('global_content_variables')
            ? GlobalContentVariable::query()->pluck('value', 'key')->all()
            : [];

        $cache = [
            'brand_name' => self::pick($vars, 'company_name', (string) config('medca.brand_name', 'Medca Healthcare')),
            'legal_name' => (string) config('medca.company_legal_name', 'Medca Healthcare'),
            'tagline' => self::pick($vars, 'tagline', (string) config('medca.tagline', 'Care You Can Trust.')),
            'short_description' => self::pick($vars, 'company_description_short', ''),
            'long_description' => self::pick($vars, 'company_description_long', ''),
            'mission' => self::pick($vars, 'mission_statement', ''),
            'vision' => self::pick($vars, 'vision_statement', ''),
            'about' => self::pick($vars, 'about_hero_subheadline', ''),
            'service_area' => self::pick($vars, 'service_area_summary', 'Home healthcare across Bangalore with focused coverage around Arekere and a 25 km service belt.'),
            'phone_display' => self::pick($vars, 'phone_number', (string) config('medca.phone_display', '')),
            'phone_e164' => self::pick($vars, 'phone_tel', (string) config('medca.phone_tel', '')),
            'whatsapp_url' => self::pick($vars, 'whatsapp', (string) config('medca.whatsapp_url', '')),
            'address' => self::pick($vars, 'address', (string) config('medca.location_display', '')),
            'business_hours' => self::pick($vars, 'business_hours', '24/7 care services, 365 days a year — including weekends and holidays.'),
            'primary_cta' => self::pick($vars, 'primary_cta', 'Call Us'),
            'secondary_cta' => self::pick($vars, 'secondary_cta', 'WhatsApp Us'),
            'trust_title' => self::pick($vars, 'trust_title', 'Why Choose Medca?'),
            'trust_pillars' => self::pick($vars, 'trust_pillars', ''),
            'care_model' => self::pick($vars, 'care_model', ''),
            'website' => rtrim((string) config('app.url'), '/'),
            'city' => (string) config('medca.default_city', 'Bangalore'),
            'region' => 'KA',
            'postal_code' => (string) config('medca.default_pincode', '560076'),
        ];

        return $cache;
    }

    public static function get(string $key, string $default = ''): string
    {
        return self::all()[$key] ?? $default;
    }

    /**
     * @return list<string>
     */
    public static function trustPillarLines(): array
    {
        $raw = self::get('trust_pillars');
        if ($raw === '') {
            return [];
        }

        $blocks = preg_split('/\n(?=[A-Z])/', $raw) ?: [];
        $lines = [];

        foreach ($blocks as $block) {
            $line = trim(preg_replace('/\s+/', ' ', $block) ?? $block);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    public static function whyMedcaParagraph(): string
    {
        $pillars = self::trustPillarLines();
        if ($pillars !== []) {
            return implode(' ', array_slice($pillars, 0, 2));
        }

        return self::get('care_model', 'Medca Healthcare combines clinical supervision, transparent family communication, and ethical home care standards across Bengaluru.');
    }

    /**
     * @return list<string>
     */
    public static function standardCareProcessSteps(): array
    {
        return [
            'Share your care needs by phone or WhatsApp for a no-obligation consultation.',
            'Receive a personalized home care plan aligned to the patient’s condition and family preferences.',
            'A verified nurse or caregiver is matched and supervised by Medca’s clinical team.',
            'Ongoing updates, medication support, and plan adjustments as recovery needs change.',
        ];
    }

    /**
     * @param  array<string, string>  $vars
     */
    private static function pick(array $vars, string $key, string $fallback): string
    {
        $value = trim((string) ($vars[$key] ?? ''));

        return $value !== '' ? $value : $fallback;
    }
}
