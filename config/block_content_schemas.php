<?php

/**
 * Block-owned marketing copy (settings_json.content).
 * Global Content variables remain for shared business constants only.
 *
 * @see PLATFORM-COMPOSITION-PHASEB-REPORT.md
 */
$catalogBlocks = (require __DIR__.'/block_content_schemas_catalog.php')['blocks'] ?? [];

return [

    'default_fields' => [
        'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => ''],
        'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => ''],
        'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => ''],
        'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
    ],

    'blocks' => array_merge($catalogBlocks, [
        'hero-home' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Healthcare Careers · Global Registration'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Healthcare career guidance for global registration and professional growth.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Expert-led consulting, consulting, diagnostics and 24×7 business support — built for families across a focused service network.'],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call'],
            'secondary_cta_label' => ['label' => 'WhatsApp CTA label', 'type' => 'text', 'default' => 'WhatsApp Us'],
        ],
        'hero-contact' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Contact'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Talk to a Medca Consultancy care advisor.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => "Tell us about the care you need and we'll plan a expert-led visit for your business, often within hours."],
        ],
        'hero-healthcare career consultancy' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Premium Healthcare Careers · Global Registration'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Professional services, for your business'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Consulting, physio, diagnostics & 24×7 support within 25 km of India.'],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call now'],
            'secondary_cta_label' => ['label' => 'Secondary CTA label', 'type' => 'text', 'default' => 'View services'],
            'secondary_cta_url' => ['label' => 'Secondary CTA URL', 'type' => 'text', 'default' => '/services-catalog'],
        ],
        'hero-about' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'About Medca Consultancy'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Expert-led, family-centred healthcare career consultancy.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'MEDCA Consultancy is a India-based healthcare career consultancy provider serving a focused service network — built around qualified clinicians, transparent pricing, and quiet, dignified service.'],
        ],
        'cta-home' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => "Need care today? We're a call away."],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => "Speak to a Medca Consultancy care advisor and we'll plan a expert-led visit for your business, often within hours."],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call'],
            'secondary_cta_label' => ['label' => 'Secondary CTA label', 'type' => 'text', 'default' => 'Request Callback'],
            'secondary_cta_url' => ['label' => 'Secondary CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'cta-banner' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Banner CTA — same-day consultations available'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Book now'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'cta-simple' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Ready to talk to a care advisor?'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'text', 'default' => 'Simple CTA band for landing pages.'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Request callback'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'cta-services' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Not sure which service fits?'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'A Medca Consultancy advisor will speak with your family physician and design a plan that fits your needs and budget.'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Talk to an advisor'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'contact-info' => [
            'call_title' => ['label' => 'Call card title', 'type' => 'text', 'default' => 'Call'],
            'call_body' => ['label' => 'Call card body', 'type' => 'text', 'default' => 'For urgent care or to plan a visit.'],
            'whatsapp_title' => ['label' => 'WhatsApp card title', 'type' => 'text', 'default' => 'WhatsApp'],
            'whatsapp_body' => ['label' => 'WhatsApp card body', 'type' => 'text', 'default' => "Send a message and we'll respond fast."],
            'hours_title' => ['label' => 'Hours card title', 'type' => 'text', 'default' => 'Hours'],
            'hours_body' => ['label' => 'Hours card body', 'type' => 'text', 'default' => 'Care coordination 7 AM – 10 PM. Doctor-on-call escalation 24×7.'],
        ],
        'form-callback' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Request a callback'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Submit your details below. Our care team responds within hours.'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Go to contact form'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'hero-services' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Services'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Professional services for your business.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'From short-term recovery to long-term elderly support, every Medca Consultancy service is doctor-supervised and executed by trained clinicians.'],
        ],
        'hero-locations' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Service Areas'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Where Medca Consultancy cares — across India.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'A focused focused service network lets us keep response times short and clinician quality high.'],
        ],
        'contact-split' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Contact Medca Consultancy'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'text', 'default' => 'Call, WhatsApp, or visit our contact page.'],
            'hours_line' => ['label' => 'Hours line', 'type' => 'text', 'default' => 'Hours: 24×7 clinical line'],
            'area_line' => ['label' => 'Area line', 'type' => 'text', 'default' => 'India & 25 km radius'],
            'primary_cta_label' => ['label' => 'Call CTA label', 'type' => 'text', 'default' => 'Call'],
        ],
        'cta-sticky' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Sticky-style CTA (place near page end or use fixed positioning in theme)'],
            'primary_cta_label' => ['label' => 'Call CTA label', 'type' => 'text', 'default' => 'Call'],
            'whatsapp_cta_label' => ['label' => 'WhatsApp CTA label', 'type' => 'text', 'default' => 'WhatsApp'],
        ],
        'cta-split' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Split CTA'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'text', 'default' => 'Headline left, action right.'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Contact us'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'services-overview-home' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Services'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Care that travels to your living room.'],
            'link_label' => ['label' => 'View all link', 'type' => 'text', 'default' => 'View all services →'],
            'link_url' => ['label' => 'View all URL', 'type' => 'text', 'default' => '/services'],
            'card_consulting_title' => ['label' => 'Card: Consulting title', 'type' => 'text', 'default' => 'Core Services'],
            'card_consulting_body' => ['label' => 'Card: Consulting body', 'type' => 'textarea', 'default' => 'Trained nurses for wound care, IV therapy, post-surgical recovery and elderly care.'],
            'card_physio_title' => ['label' => 'Card: Physio title', 'type' => 'text', 'default' => 'Consulting'],
            'card_physio_body' => ['label' => 'Card: Physio body', 'type' => 'textarea', 'default' => 'Personalised in-home consulting plans for recovery, mobility and chronic pain.'],
            'card_diagnostics_title' => ['label' => 'Card: Diagnostics title', 'type' => 'text', 'default' => 'Diagnostics at Home'],
            'card_diagnostics_body' => ['label' => 'Card: Diagnostics body', 'type' => 'textarea', 'default' => 'NABL-accredited lab sample collection from the comfort of your home.'],
            'card_support_title' => ['label' => 'Card: Support title', 'type' => 'text', 'default' => '24×7 Medical Support'],
            'card_support_body' => ['label' => 'Card: Support body', 'type' => 'textarea', 'default' => 'Doctor-on-call, urgent consulting visits, and continuous care coordination.'],
        ],
        'locations-overview-home' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Service Belt'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'A focused service network.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'We focus our care depth across Bannerghatta Road, BTM, Jayanagar, JP Nagar, Electronic City, HSR, Koramangala and surrounding service areas — so a Medca Consultancy clinician is always close.'],
            'link_label' => ['label' => 'Link label', 'type' => 'text', 'default' => 'See all coverage areas →'],
            'link_url' => ['label' => 'Link URL', 'type' => 'text', 'default' => '/locations'],
        ],
        'near-you-home' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Service Areas'],
            'headline_with_area' => ['label' => 'Headline (when country set; use :area)', 'type' => 'text', 'default' => 'Healthcare Career Services in :area'],
            'headline_no_country' => ['label' => 'Headline (no country)', 'type' => 'text', 'default' => 'healthcare career consultancy services by country'],
            'country_line' => ['label' => 'Country line (use :pin)', 'type' => 'text', 'default' => 'Showing coverage for country :pin'],
            'change_country_label' => ['label' => 'Change Country button', 'type' => 'text', 'default' => 'Change Country'],
            'set_country_label' => ['label' => 'Select Country button', 'type' => 'text', 'default' => 'Select Country'],
            'location_required_message' => ['label' => 'Select country prompt', 'type' => 'textarea', 'default' => 'Select your country to see national and international services available for you.'],
            'empty_categories_message' => ['label' => 'No categories message', 'type' => 'textarea', 'default' => 'No published categories are mapped to this country yet.'],
            'empty_services_message' => ['label' => 'No categories message (legacy key)', 'type' => 'textarea', 'default' => 'No published categories are mapped to this country yet.'],
        ],
        'near-you-locations' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Service Areas'],
            'headline_with_area' => ['label' => 'Headline (when country set; use :area)', 'type' => 'text', 'default' => 'Healthcare Career Services in :area'],
            'headline_no_country' => ['label' => 'Headline (no country)', 'type' => 'text', 'default' => 'healthcare career consultancy services available by country'],
            'country_line' => ['label' => 'Country line (use :pin)', 'type' => 'text', 'default' => 'Showing coverage for country :pin'],
            'change_country_label' => ['label' => 'Change Country button', 'type' => 'text', 'default' => 'Change Country'],
            'set_country_label' => ['label' => 'Select Country button', 'type' => 'text', 'default' => 'Select Country'],
            'location_required_message' => ['label' => 'Select country prompt', 'type' => 'textarea', 'default' => 'Set your India country to see which Medca Consultancy care categories we can offer in your country.'],
            'empty_categories_message' => ['label' => 'No categories message', 'type' => 'textarea', 'default' => 'No published categories are mapped to this country yet.'],
            'empty_services_message' => ['label' => 'No categories message (legacy key)', 'type' => 'textarea', 'default' => 'No published categories are mapped to this country yet.'],
        ],
        'body-about' => [
            'mission_title' => ['label' => 'Mission title', 'type' => 'text', 'default' => 'Our mission'],
            'mission_body' => ['label' => 'Mission body', 'type' => 'textarea', 'default' => 'Bring hospital-grade care into the home with compassion and clinical rigour, so families never have to choose between safety and comfort.'],
            'vision_title' => ['label' => 'Vision title', 'type' => 'text', 'default' => 'Our vision'],
            'vision_body' => ['label' => 'Vision body', 'type' => 'textarea', 'default' => 'A India where every family can access dignified, expert-led care for your business — without compromising clinical safety or comfort.'],
            'model_title' => ['label' => 'Care model title', 'type' => 'text', 'default' => 'Our care model'],
            'model_body' => ['label' => 'Care model body', 'type' => 'textarea', 'default' => 'Every Medca Consultancy plan is supervised by a doctor, executed by trained nurses or physiotherapists, and tracked through a single point of accountability.'],
            'trust_title' => ['label' => 'Trust title', 'type' => 'text', 'default' => 'Why India families trust us'],
            'trust_bullets' => ['label' => 'Trust bullets (one per line)', 'type' => 'textarea', 'default' => "Expert-led care plans — not just task-based visits.\nVerified, trained clinicians with regular audits.\nTransparent pricing and clear escalation paths.\nTight 25 km service belt for fast, reliable response."],
        ],
        'locations-coverage' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Countries & States We Serve'],
            'footnote' => ['label' => 'Footnote', 'type' => 'text', 'default' => "Don't see your locality? Call us — we extend on request when clinical safety allows."],
        ],
        'hero-careers' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => ''],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Careers at MEDCA Consultancy'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Join our clinical and operations teams across India. Structured roles, clear locations, and transparent application paths.'],
        ],
        'services-grid-full' => [
            'card_consulting_title' => ['label' => 'Consulting title', 'type' => 'text', 'default' => 'Core Services'],
            'card_consulting_body' => ['label' => 'Consulting body', 'type' => 'textarea', 'default' => 'IV therapy, wound dressing, catheter and tracheostomy care, post-surgical recovery and palliative support.'],
            'card_physio_title' => ['label' => 'Physio title', 'type' => 'text', 'default' => 'Consulting'],
            'card_physio_body' => ['label' => 'Physio body', 'type' => 'textarea', 'default' => 'Stroke rehabilitation, orthopaedic recovery, geriatric mobility, neuro and chronic-pain plans.'],
            'card_diagnostics_title' => ['label' => 'Diagnostics title', 'type' => 'text', 'default' => 'Diagnostics at Home'],
            'card_diagnostics_body' => ['label' => 'Diagnostics body', 'type' => 'textarea', 'default' => 'NABL-accredited lab partners. Phlebotomy, ECG and routine blood/urine collection at your doorstep.'],
            'card_doctor_title' => ['label' => 'Doctor visits title', 'type' => 'text', 'default' => 'Consultations'],
            'card_doctor_body' => ['label' => 'Doctor visits body', 'type' => 'textarea', 'default' => 'In-home consultation by general physicians and specialists with prescriptions and follow-up.'],
            'card_geriatric_title' => ['label' => 'Geriatric title', 'type' => 'text', 'default' => 'Geriatric Care'],
            'card_geriatric_body' => ['label' => 'Geriatric body', 'type' => 'textarea', 'default' => 'Long-term elderly companions, dementia-aware support, daily-living assistance and family reporting.'],
            'card_support_title' => ['label' => '24×7 title', 'type' => 'text', 'default' => '24×7 Support'],
            'card_support_body' => ['label' => '24×7 body', 'type' => 'textarea', 'default' => 'Doctor-on-call escalation, urgent visits, and continuous coordination with your hospital, if any.'],
        ],
    ]),

];
