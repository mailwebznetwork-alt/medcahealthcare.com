<?php

namespace Database\Seeders;

use App\Models\GlobalContentVariable;
use App\Services\Deployment\GlobalContentVariableRepository;
use Illuminate\Database\Seeder;

/**
 * Launch-time global contact and brand values (single source for phone / WhatsApp in blocks).
 */
class MedcaLaunchGlobalContentSeeder extends Seeder
{
    public function run(): void
    {
        $values = [
            'company_name' => config('medca.brand_name', 'Medca Health Care'),
            'tagline' => config('medca.tagline', 'Care You Can Trust'),
            'phone_number' => env('MEDCA_PHONE_DISPLAY', '+91 88849 99002'),
            'phone_tel' => env('MEDCA_PHONE_TEL', 'tel:+918884999002'),
            'whatsapp' => env('MEDCA_WHATSAPP_URL', 'https://wa.me/918884999002'),
            'address' => env('MEDCA_LOCATION', 'Arekere Gate, Bannerghatta Road, Bengaluru — 560076'),
            'primary_cta' => 'Book a home visit',
            'secondary_cta' => 'WhatsApp our care team',
            'business_hours' => 'Care coordination 7 AM – 10 PM. Doctor-on-call escalation 24×7.',
            'company_description_short' => 'Doctor-led home healthcare across a 25 km belt around Arekere, Bangalore.',
            'company_description_long' => 'Medca Health Care is a Bangalore-based premium home healthcare provider serving a 25 km belt around Arekere — built around qualified clinicians, transparent pricing, and quiet, dignified service.',
            'mission_title' => 'Our mission',
            'mission_statement' => 'Bring hospital-grade care into the home with compassion and clinical rigour, so families never have to choose between safety and comfort.',
            'vision_title' => 'Our vision',
            'vision_statement' => 'A Bangalore where every family can access dignified, doctor-led care at home — without compromising clinical safety or comfort.',
            'care_model_title' => 'Our care model',
            'care_model' => 'Every Medca plan is supervised by a doctor, executed by trained nurses or physiotherapists, and tracked through a single point of accountability.',
            'trust_title' => 'Why Bangalore families trust us',
            'trust_pillars' => "Doctor-led care plans — not just task-based visits.\nVerified, trained clinicians with regular audits.\nTransparent pricing and clear escalation paths.\nTight 25 km service belt for fast, reliable response.",
            'service_area_summary' => 'Premium home healthcare across a 25 km belt around Arekere, Bangalore.',
            'home_hero_eyebrow' => 'Premium Home Healthcare · Bangalore',
            'home_hero_headline' => 'Premium home healthcare, delivered to your doorstep in Bangalore.',
            'home_hero_subheadline' => 'Doctor-led nursing, physiotherapy, diagnostics and 24×7 medical support — built for families across a 25 km belt around Arekere.',
            'about_hero_eyebrow' => 'About Medca',
            'about_hero_headline' => 'Doctor-led, family-centred home healthcare.',
            'about_hero_subheadline' => 'Medca Health Care is a Bangalore-based premium home healthcare provider serving a 25 km belt around Arekere — built around qualified clinicians, transparent pricing, and quiet, dignified service.',
            'contact_hero_headline' => 'Talk to a Medca care advisor.',
            'contact_hero_subheadline' => "Tell us about the care you need and we'll plan a doctor-led visit at home, often within hours.",
            'response_time_promise' => 'Doctor-led visit planning, often within hours.',
            'city' => 'Bangalore',
            'pincode' => '560076',
        ];

        foreach ($values as $key => $value) {
            GlobalContentVariable::query()->updateOrCreate(
                ['key' => $key],
                [
                    'label' => (string) (config("global_content_variables.keys.{$key}.label") ?? $key),
                    'value' => $value,
                ]
            );
        }

        GlobalContentVariableRepository::forgetCache();
    }
}
