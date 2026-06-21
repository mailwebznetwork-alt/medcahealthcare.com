<?php

namespace App\Services\Public;

use Illuminate\Support\Str;

/**
 * Default Lucide icon names for catalog entities and key benefits.
 * Stored values in DB take precedence; this provides intelligent fallbacks.
 */
final class CatalogLineIconMapper
{
    /** @var array<string, string> */
    private const CATEGORY_CODES = [
        'cat-support-services' => 'heart-handshake',
        'cat-home-consulting-services' => 'stethoscope',
        'cat-consulting-services' => 'person-standing',
        'cat-medical-lab-services' => 'flask-conical',
        'cat-medical-equipment-sales---rentals' => 'bed-double',
        'cat-doctor-therapy-services' => 'user-round-cog',
        'cat-doctor-and-therapy-services' => 'user-round-cog',
    ];

    /** @var array<string, string> */
    private const BENEFIT_LABELS = [
        'certified experts' => 'badge-check',
        'trained professionals' => 'user-check',
        'personalized care plans' => 'clipboard-list',
        'flexible scheduling' => 'calendar-clock',
        '24/7 services' => 'clock',
        '24/7 support' => 'headset',
        'affordable pricing' => 'wallet',
        'affordable services' => 'wallet',
        'transparent pricing' => 'receipt',
        'quality care' => 'heart-pulse',
        'fast response' => 'zap',
        'verified staff' => 'shield-check',
    ];

    /** @var array<int, array{keywords: list<string>, icon: string}> */
    private const KEYWORD_RULES = [
        ['keywords' => ['elderly', 'geriatric', 'senior'], 'icon' => 'users'],
        ['keywords' => ['bedridden', 'bed sore', 'position changing'], 'icon' => 'bed'],
        ['keywords' => ['post hospital', 'post-hospital', 'post surgical', 'post-surgical', 'postnatal'], 'icon' => 'hospital'],
        ['keywords' => ['dementia', 'alzheimer', 'memory', 'cognitive'], 'icon' => 'brain'],
        ['keywords' => ['parkinson'], 'icon' => 'activity'],
        ['keywords' => ['stroke'], 'icon' => 'heart-pulse'],
        ['keywords' => ['disability', 'mobility', 'walker', 'wheelchair', 'gait'], 'icon' => 'accessibility'],
        ['keywords' => ['cancer', 'oncology', 'palliative', 'comfort care'], 'icon' => 'ribbon'],
        ['keywords' => ['bathing', 'grooming', 'hygiene', 'toileting'], 'icon' => 'shower-head'],
        ['keywords' => ['feeding', 'nutrition', 'peg', 'tube feeding'], 'icon' => 'utensils'],
        ['keywords' => ['medication', 'injection', 'insulin', 'infusion', 'iv '], 'icon' => 'pill'],
        ['keywords' => ['companionship', 'emotional', 'behaviour', 'behavior'], 'icon' => 'messages-square'],
        ['keywords' => ['fall prevention', 'safety supervision', 'safety'], 'icon' => 'shield-alert'],
        ['keywords' => ['consulting', 'physio', 'rehabilitation', 'rehab', 'exercise', 'strength', 'balance', 'pain management', 'sports'], 'icon' => 'dumbbell'],
        ['keywords' => ['consulting', 'wound', 'dressing', 'catheter', 'tracheostomy', 'ventilator', 'icu', 'critical care', 'airway', 'suction', 'nebulizer'], 'icon' => 'cross'],
        ['keywords' => ['blood test', 'lab', 'profile', 'cbc', 'lipid', 'thyroid', 'diabetes monitoring', 'hba1c', 'bilirubin', 'creatinine', 'urea', 'esr', 'sgot', 'sgpt', 'vitamin', 't3', 't4', 'tsh'], 'icon' => 'test-tubes'],
        ['keywords' => ['health checkup', 'assessment', 'monitoring', 'vital'], 'icon' => 'clipboard-plus'],
        ['keywords' => ['oxygen', 'bipap', 'cpap', 'concentrator', 'cylinder'], 'icon' => 'wind'],
        ['keywords' => ['hospital bed', 'icu bed', 'commode', 'patient monitor'], 'icon' => 'bed-double'],
        ['keywords' => ['mother', 'baby', 'newborn', 'pediatric'], 'icon' => 'baby'],
        ['keywords' => ['rental', 'sales', 'installation', 'refilling'], 'icon' => 'package'],
        ['keywords' => ['daily living', 'personal care', 'personal assistance', 'daily routine', 'daily activity'], 'icon' => 'hand-helping'],
        ['keywords' => ['recovery', 'follow-up'], 'icon' => 'trending-up'],
        ['keywords' => ['community participation'], 'icon' => 'map-pin-house'],
    ];

    public function categoryIcon(?string $code, ?string $name): string
    {
        $code = Str::lower(trim((string) $code));
        if ($code !== '' && isset(self::CATEGORY_CODES[$code])) {
            return self::CATEGORY_CODES[$code];
        }

        return $this->fromKeywords($name, 'layout-grid');
    }

    public function serviceIcon(?string $code, ?string $title): string
    {
        return $this->fromKeywords(trim((string) $code).' '.trim((string) $title), 'heart-pulse');
    }

    public function subServiceIcon(?string $code, ?string $title): string
    {
        return $this->fromKeywords(trim((string) $code).' '.trim((string) $title), 'circle-dot');
    }

    public function benefitIcon(string $label): string
    {
        $normalized = Str::lower(trim($label));
        if (isset(self::BENEFIT_LABELS[$normalized])) {
            return self::BENEFIT_LABELS[$normalized];
        }

        foreach (self::BENEFIT_LABELS as $pattern => $icon) {
            if (Str::contains($normalized, $pattern)) {
                return $icon;
            }
        }

        return $this->fromKeywords($label, 'check');
    }

    private function fromKeywords(string $haystack, string $fallback): string
    {
        $haystack = Str::lower($haystack);

        foreach (self::KEYWORD_RULES as $rule) {
            foreach ($rule['keywords'] as $keyword) {
                if (Str::contains($haystack, Str::lower($keyword))) {
                    return $rule['icon'];
                }
            }
        }

        return $fallback;
    }
}
