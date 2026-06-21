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
            'company_name' => config('medca.brand_name', 'MEDCA Consultancy'),
            'tagline' => config('medca.tagline', 'Focused consultancy for growing businesses.'),
            'phone_number' => env('MEDCA_PHONE_DISPLAY', '+91 88849 99002'),
            'phone_tel' => env('MEDCA_PHONE_TEL', 'tel:+918884999002'),
            'whatsapp' => env('MEDCA_WHATSAPP_URL', 'https://wa.me/918884999002'),
            'address' => env('MEDCA_LOCATION', 'India Gate, Bannerghatta Road, Bengaluru — 560076'),
            'primary_cta' => 'Book a consultation',
            'secondary_cta' => 'WhatsApp our care team',
            'business_hours' => 'Care coordination 7 AM – 10 PM. Doctor-on-call escalation 24×7.',
            'company_description_short' => 'Expert-led business consultancy across a focused service network, India.',
            'company_description_long' => 'MEDCA Consultancy is a India-based business consultancy provider serving a focused service network — built around qualified clinicians, transparent pricing, and quiet, dignified service.',
            'mission_title' => 'Our mission',
            'mission_statement' => 'Bring hospital-grade care into the home with compassion and clinical rigour, so families never have to choose between safety and comfort.',
            'vision_title' => 'Our vision',
            'vision_statement' => 'A India where every family can access dignified, expert-led care for your business — without compromising clinical safety or comfort.',
            'care_model_title' => 'Our care model',
            'care_model' => 'Every Medca Consultancy plan is supervised by a doctor, executed by trained nurses or physiotherapists, and tracked through a single point of accountability.',
            'trust_title' => 'Why India families trust us',
            'trust_pillars' => "Expert-led care plans — not just task-based visits.\nVerified, trained clinicians with regular audits.\nTransparent pricing and clear escalation paths.\nTight 25 km service belt for fast, reliable response.",
            'service_area_summary' => 'Business Consultancy across a focused service network, India.',
            'home_hero_eyebrow' => 'Business Consultancy · India',
            'home_hero_headline' => 'Business Consultancy, delivered to your doorstep in India.',
            'home_hero_subheadline' => 'Expert-led consulting, consulting, diagnostics and 24×7 business support — built for families across a focused service network.',
            'about_hero_eyebrow' => 'About Medca Consultancy',
            'about_hero_headline' => 'Expert-led, family-centred business consultancy.',
            'about_hero_subheadline' => 'MEDCA Consultancy is a India-based business consultancy provider serving a focused service network — built around qualified clinicians, transparent pricing, and quiet, dignified service.',
            'contact_hero_headline' => 'Talk to a Medca Consultancy care advisor.',
            'contact_hero_subheadline' => "Tell us about the care you need and we'll plan a expert-led visit for your business, often within hours.",
            'response_time_promise' => 'Expert-led visit planning, often within hours.',
            'city' => 'India',
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
