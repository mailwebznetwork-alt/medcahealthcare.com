<?php

use App\Models\Block;
use App\Models\GlobalContentVariable;
use App\Models\Page;
use App\Services\Deployment\GlobalContentVariableRepository;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$address = 'SLN Complex, 2nd Floor, Arekere Gate, Bannerghatta Road, Bengaluru, Karnataka 560076';

$blockUpdates = [
    'hero-home' => [
        'eyebrow' => 'Trusted Home Healthcare in Bengaluru',
        'headline' => 'Your Loved Ones Deserve the Best Care, Right at Home.',
        'subheadline' => 'Expert nursing, dedicated elderly support, and compassionate recovery services across Bengaluru. We bring the expertise of a hospital to the comfort of your living room.',
        'primary_cta_label' => 'Call Us',
        'secondary_cta_label' => 'WhatsApp Us',
        'secondary_cta_url' => '',
    ],
    'body-about' => [
        'trust_title' => 'Why Choose Medca?',
        'trust_bullets' => implode("\n", [
            'Verified Professionalism|Every member of our team undergoes rigorous background checks and clinical competency training. We only send professionals we would trust with our own families.',
            'Personalized Care Blueprints|No two patients are the same. We create a customized care plan that evolves with the patient\'s recovery and specific needs.',
            'Unwavering Reliability|From timely medication administration to 24/7 bedside support, we ensure your loved one is never left without the attention they deserve.',
            'Compassionate Connection|We believe care is more than just clinical tasks—it\'s about dignity, companionship, and improving the quality of life every single day.',
        ]),
        'promise_headline' => 'The Medca Quality Promise',
        'promise_intro' => 'We hold ourselves to a standard higher than the industry average. Our commitment to you:',
        'mission_title' => 'Clinical Supervision',
        'mission_body' => 'Our caregivers are guided by senior nursing supervisors to ensure quality is maintained 24/7.',
        'vision_title' => 'Transparent Communication',
        'vision_body' => 'We maintain open channels with families, providing regular updates on the patient\'s health and progress.',
        'model_title' => 'Ethical Care Standards',
        'model_body' => 'We operate with complete integrity, ensuring every service provided is necessary, transparent, and focused purely on patient recovery.',
    ],
    'services-overview-home' => [
        'eyebrow' => 'Our Core Services',
        'headline' => 'Hospital-grade care, delivered where you need it most.',
        'link_label' => 'View all services',
        'link_url' => '/services',
        'card_nursing_title' => 'Professional Home Nursing',
        'card_nursing_body' => "Clinical excellence where you need it most.\nPost-Surgical Recovery & Wound Care\nICU/HDU Setup Support at Home\nTracheostomy, Catheter, and Ryles Tube Care\nIV Infusions & Vital Signs Monitoring",
        'card_support_title' => 'Elderly Care & Companionship',
        'card_support_body' => "Dignity and support for their golden years.\nAssistance with Daily Living (Mobility, Hygiene, Feeding)\nMedication Management & Reminders\nCognitive Support for Dementia/Alzheimer's\nCompanionship & Emotional Well-being",
        'card_physio_title' => 'Physiotherapy & Rehabilitation',
        'card_physio_body' => 'Regain your strength, step by step. Our certified therapists design personalized rehabilitation programs to help patients recover mobility and independence after surgery or illness.',
        'card_diagnostics_title' => 'Doctor Visits at Home',
        'card_diagnostics_body' => 'Expert medical advice, without the travel. Eliminate the stress of hospital commutes. Our experienced physicians visit your home for routine check-ups, chronic disease management, and urgent consultations.',
    ],
    'process-steps' => [
        'headline' => 'How We Start',
        'subheadline' => 'The Peace of Mind Process — from first call to ongoing care.',
        'steps' => implode("\n", [
            'Listen|You contact us and share your specific needs.',
            'Assess|We conduct a professional, no-obligation assessment of the patient\'s health and home environment.',
            'Plan|We create a bespoke care plan tailored to the patient\'s recovery goals.',
            'Care|Our trained, verified professional arrives exactly when scheduled to begin care.',
            'Monitor|We review the care plan regularly to ensure it continues to meet the patient\'s changing needs.',
        ]),
    ],
    'faq-accordion' => [
        'headline' => 'Frequently Asked Questions',
        'faq_items' => implode("\n", [
            'How do I know the caregivers are trustworthy?|We perform strict background verification and identity checks on all staff. Your family\'s safety is our top priority.',
            'What if I need care immediately?|We understand that health needs can be urgent. Our coordination team is available to mobilize care as quickly as possible.',
            'Is your service available on weekends and holidays?|Yes, our care services operate 24/7, 365 days a year.',
            'Do you work with families living outside Bengaluru?|We specialize in local care coordination. If you are away, we act as your "eyes and ears" on the ground, keeping you updated via phone or digital communication.',
        ]),
    ],
    'cta-home' => [
        'headline' => 'Ready to give your loved ones the care they deserve?',
        'subheadline' => 'Call us or message on WhatsApp. Our care team is here to guide you every step of the way.',
        'primary_cta_label' => 'Call Us',
        'secondary_cta_label' => 'WhatsApp Us',
        'secondary_cta_url' => '',
    ],
    'hero-about' => [
        'eyebrow' => 'About Medca Healthcare',
        'headline' => 'Providing Compassionate Care Across Bengaluru',
        'subheadline' => 'Medca Healthcare brings hospital-grade nursing, elderly support, and recovery services to the comfort of your home. We combine clinical excellence with dignity, reliability, and genuine human connection.',
    ],
    'hero-contact' => [
        'eyebrow' => 'Contact',
        'headline' => 'Get in Touch',
        'subheadline' => 'Tell us about your loved one\'s needs and we will arrange a free home assessment with no obligation.',
    ],
    'contact-info' => [
        'call_title' => 'Call Us',
        'call_body' => 'Reach our coordination team for nursing, elderly care, physiotherapy, and recovery support at home.',
        'whatsapp_title' => 'WhatsApp',
        'whatsapp_body' => 'Send us a message and our care team will respond as quickly as possible.',
        'hours_title' => 'Availability',
        'hours_body' => '24/7 care services, 365 days a year — including weekends and holidays.',
    ],
];

foreach ($blockUpdates as $slug => $content) {
    $block = Block::query()->where('block_slug', $slug)->first();
    if (! $block) {
        echo "Missing block: {$slug}\n";
        continue;
    }
    $settings = is_array($block->settings_json) ? $block->settings_json : [];
    $settings['content'] = array_merge($settings['content'] ?? [], $content);
    $block->settings_json = $settings;
    $block->save();
    echo "Updated block: {$slug}\n";
}

$homeContent = implode("\n", [
    '{{block:hero-home}}',
    '{{block:body-about}}',
    '{{block:services-overview-home}}',
    '{{block:process-steps}}',
    '{{block:faq-accordion}}',
    '{{block:near-you-home}}',
    '{{block:locations-overview-home}}',
    '{{block:cta-home}}',
    '{{block:testimonials-carousel}}',
    '{{block:testimonials-grid}}',
    '{{block:testimonials-highlight}}',
]);

$page = Page::query()->where('slug', 'home')->first();
if ($page) {
    $page->content = $homeContent;
    $page->save();
    echo "Updated home page composition\n";
}

$globals = [
    'home_hero_headline' => 'Your Loved Ones Deserve the Best Care, Right at Home.',
    'home_hero_subheadline' => 'Expert nursing, dedicated elderly support, and compassionate recovery services across Bengaluru. We bring the expertise of a hospital to the comfort of your living room.',
    'home_hero_eyebrow' => 'Trusted Home Healthcare in Bengaluru',
    'company_description_short' => 'Expert nursing, dedicated elderly support, and compassionate recovery services across Bengaluru.',
    'company_description_long' => 'Medca Healthcare brings hospital-grade nursing, elderly support, and recovery services to the comfort of your home across Bengaluru.',
    'address' => $address,
    'trust_title' => 'Why Choose Medca?',
    'trust_pillars' => $blockUpdates['body-about']['trust_bullets'],
    'mission_title' => 'Clinical Supervision',
    'mission_statement' => 'Our caregivers are guided by senior nursing supervisors to ensure quality is maintained 24/7.',
    'vision_title' => 'Transparent Communication',
    'vision_statement' => 'We maintain open channels with families, providing regular updates on the patient\'s health and progress.',
    'care_model_title' => 'Ethical Care Standards',
    'care_model' => 'We operate with complete integrity, ensuring every service provided is necessary, transparent, and focused purely on patient recovery.',
    'about_hero_headline' => 'Providing Compassionate Care Across Bengaluru',
    'about_hero_subheadline' => 'Medca Healthcare brings hospital-grade nursing, elderly support, and recovery services to the comfort of your home.',
    'about_hero_eyebrow' => 'About Medca Healthcare',
    'contact_hero_headline' => 'Get in Touch',
    'contact_hero_subheadline' => 'Tell us about your loved one\'s needs and we will arrange a free home assessment with no obligation.',
    'contact_hero_eyebrow' => 'Contact',
    'business_hours' => '24/7 care services, 365 days a year — including weekends and holidays.',
    'tagline' => 'Care You Can Trust.',
    'primary_cta' => 'Call Us',
    'secondary_cta' => 'WhatsApp Us',
];

foreach ($globals as $key => $value) {
    $label = (string) (config("global_content_variables.keys.{$key}.label") ?? $key);
    GlobalContentVariable::query()->updateOrCreate(
        ['key' => $key],
        ['label' => $label, 'value' => $value]
    );
}

GlobalContentVariableRepository::forgetCache();
echo "Updated global content variables\n";
echo "Done.\n";
