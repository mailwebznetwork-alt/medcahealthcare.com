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
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Premium Home Healthcare · Bangalore'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Premium home healthcare, delivered to your doorstep in Bangalore.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Doctor-led nursing, physiotherapy, diagnostics and 24×7 medical support — built for families across a 25 km belt around Arekere.'],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call'],
            'secondary_cta_label' => ['label' => 'WhatsApp CTA label', 'type' => 'text', 'default' => 'WhatsApp Us'],
        ],
        'hero-contact' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Contact'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Talk to a Medca care advisor.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => "Tell us about the care you need and we'll plan a doctor-led visit at home, often within hours."],
        ],
        'hero-healthcare' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Premium Healthcare · Bangalore'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Hospital-grade care, at home'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Nursing, physio, diagnostics & 24×7 support within 25 km of Arekere.'],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call now'],
            'secondary_cta_label' => ['label' => 'Secondary CTA label', 'type' => 'text', 'default' => 'View services'],
            'secondary_cta_url' => ['label' => 'Secondary CTA URL', 'type' => 'text', 'default' => '/services-catalog'],
        ],
        'hero-about' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'About Medca'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Doctor-led, family-centred home healthcare.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Medca Health Care is a Bangalore-based premium home healthcare provider serving a 25 km belt around Arekere — built around qualified clinicians, transparent pricing, and quiet, dignified service.'],
        ],
        'cta-home' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => "Need care today? We're a call away."],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => "Speak to a Medca care advisor and we'll plan a doctor-led visit at home, often within hours."],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call'],
            'secondary_cta_label' => ['label' => 'Secondary CTA label', 'type' => 'text', 'default' => 'Request Callback'],
            'secondary_cta_url' => ['label' => 'Secondary CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'cta-banner' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Banner CTA — same-day home visits available'],
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
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'A Medca advisor will speak with your family physician and design a plan that fits your needs and budget.'],
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
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Hospital-grade care at home.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'From short-term recovery to long-term elderly support, every Medca service is doctor-supervised and executed by trained clinicians.'],
        ],
        'hero-locations' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Service Areas'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Where Medca cares — across Bangalore.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'A focused 25 km belt around Arekere lets us keep response times short and clinician quality high.'],
        ],
        'contact-split' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Contact Medca'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'text', 'default' => 'Call, WhatsApp, or visit our contact page.'],
            'hours_line' => ['label' => 'Hours line', 'type' => 'text', 'default' => 'Hours: 24×7 clinical line'],
            'area_line' => ['label' => 'Area line', 'type' => 'text', 'default' => 'Arekere & 25 km radius'],
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
            'card_nursing_title' => ['label' => 'Card: Nursing title', 'type' => 'text', 'default' => 'Home Nursing'],
            'card_nursing_body' => ['label' => 'Card: Nursing body', 'type' => 'textarea', 'default' => 'Trained nurses for wound care, IV therapy, post-surgical recovery and elderly care.'],
            'card_physio_title' => ['label' => 'Card: Physio title', 'type' => 'text', 'default' => 'Physiotherapy'],
            'card_physio_body' => ['label' => 'Card: Physio body', 'type' => 'textarea', 'default' => 'Personalised in-home physiotherapy plans for recovery, mobility and chronic pain.'],
            'card_diagnostics_title' => ['label' => 'Card: Diagnostics title', 'type' => 'text', 'default' => 'Diagnostics at Home'],
            'card_diagnostics_body' => ['label' => 'Card: Diagnostics body', 'type' => 'textarea', 'default' => 'NABL-accredited lab sample collection from the comfort of your home.'],
            'card_support_title' => ['label' => 'Card: Support title', 'type' => 'text', 'default' => '24×7 Medical Support'],
            'card_support_body' => ['label' => 'Card: Support body', 'type' => 'textarea', 'default' => 'Doctor-on-call, urgent nursing visits, and continuous care coordination.'],
        ],
        'locations-overview-home' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Service Belt'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'A 25 km belt around Arekere.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'We focus our care depth across Bannerghatta Road, BTM, Jayanagar, JP Nagar, Electronic City, HSR, Koramangala and surrounding pin codes — so a Medca clinician is always close.'],
            'link_label' => ['label' => 'Link label', 'type' => 'text', 'default' => 'See all coverage areas →'],
            'link_url' => ['label' => 'Link URL', 'type' => 'text', 'default' => '/locations'],
        ],
        'near-you-home' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Near You'],
            'headline_with_area' => ['label' => 'Headline (when pincode set; use :area)', 'type' => 'text', 'default' => 'Services in :area'],
            'headline_no_pincode' => ['label' => 'Headline (no pincode)', 'type' => 'text', 'default' => 'Services near your pincode'],
            'pincode_line' => ['label' => 'Pincode line (use :pin)', 'type' => 'text', 'default' => 'Showing coverage for pincode :pin'],
            'change_pincode_label' => ['label' => 'Change pincode button', 'type' => 'text', 'default' => 'Change pincode'],
            'set_pincode_label' => ['label' => 'Set pincode button', 'type' => 'text', 'default' => 'Set pincode'],
            'location_required_message' => ['label' => 'Set pincode prompt', 'type' => 'textarea', 'default' => 'Set your Bangalore pincode to see hyper-local services available in your area.'],
            'empty_services_message' => ['label' => 'No services message', 'type' => 'textarea', 'default' => 'No published services are mapped to this pincode yet.'],
        ],
        'near-you-locations' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Near You'],
            'headline_with_area' => ['label' => 'Headline (when pincode set; use :area)', 'type' => 'text', 'default' => 'Services in :area'],
            'headline_no_pincode' => ['label' => 'Headline (no pincode)', 'type' => 'text', 'default' => 'Services available near you'],
            'pincode_line' => ['label' => 'Pincode line (use :pin)', 'type' => 'text', 'default' => 'Showing coverage for pincode :pin'],
            'change_pincode_label' => ['label' => 'Change pincode button', 'type' => 'text', 'default' => 'Change pincode'],
            'set_pincode_label' => ['label' => 'Set pincode button', 'type' => 'text', 'default' => 'Set pincode'],
            'location_required_message' => ['label' => 'Set pincode prompt', 'type' => 'textarea', 'default' => 'Set your Bangalore pincode to see which Medca services we can offer in your area.'],
            'empty_services_message' => ['label' => 'No services message', 'type' => 'textarea', 'default' => 'No published services are mapped to this pincode yet.'],
        ],
        'body-about' => [
            'mission_title' => ['label' => 'Mission title', 'type' => 'text', 'default' => 'Our mission'],
            'mission_body' => ['label' => 'Mission body', 'type' => 'textarea', 'default' => 'Bring hospital-grade care into the home with compassion and clinical rigour, so families never have to choose between safety and comfort.'],
            'vision_title' => ['label' => 'Vision title', 'type' => 'text', 'default' => 'Our vision'],
            'vision_body' => ['label' => 'Vision body', 'type' => 'textarea', 'default' => 'A Bangalore where every family can access dignified, doctor-led care at home — without compromising clinical safety or comfort.'],
            'model_title' => ['label' => 'Care model title', 'type' => 'text', 'default' => 'Our care model'],
            'model_body' => ['label' => 'Care model body', 'type' => 'textarea', 'default' => 'Every Medca plan is supervised by a doctor, executed by trained nurses or physiotherapists, and tracked through a single point of accountability.'],
            'trust_title' => ['label' => 'Trust title', 'type' => 'text', 'default' => 'Why Bangalore families trust us'],
            'trust_bullets' => ['label' => 'Trust bullets (one per line)', 'type' => 'textarea', 'default' => "Doctor-led care plans — not just task-based visits.\nVerified, trained clinicians with regular audits.\nTransparent pricing and clear escalation paths.\nTight 25 km service belt for fast, reliable response."],
        ],
        'locations-coverage' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Areas we cover'],
            'footnote' => ['label' => 'Footnote', 'type' => 'text', 'default' => "Don't see your locality? Call us — we extend on request when clinical safety allows."],
        ],
        'hero-careers' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => ''],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Careers at Medca Health Care'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Join our clinical and operations teams across Bangalore. Structured roles, clear locations, and transparent application paths.'],
        ],
        'services-grid-full' => [
            'card_nursing_title' => ['label' => 'Nursing title', 'type' => 'text', 'default' => 'Home Nursing'],
            'card_nursing_body' => ['label' => 'Nursing body', 'type' => 'textarea', 'default' => 'IV therapy, wound dressing, catheter and tracheostomy care, post-surgical recovery and palliative support.'],
            'card_physio_title' => ['label' => 'Physio title', 'type' => 'text', 'default' => 'Physiotherapy'],
            'card_physio_body' => ['label' => 'Physio body', 'type' => 'textarea', 'default' => 'Stroke rehabilitation, orthopaedic recovery, geriatric mobility, neuro and chronic-pain plans.'],
            'card_diagnostics_title' => ['label' => 'Diagnostics title', 'type' => 'text', 'default' => 'Diagnostics at Home'],
            'card_diagnostics_body' => ['label' => 'Diagnostics body', 'type' => 'textarea', 'default' => 'NABL-accredited lab partners. Phlebotomy, ECG and routine blood/urine collection at your doorstep.'],
            'card_doctor_title' => ['label' => 'Doctor visits title', 'type' => 'text', 'default' => 'Doctor Visits'],
            'card_doctor_body' => ['label' => 'Doctor visits body', 'type' => 'textarea', 'default' => 'In-home consultation by general physicians and specialists with prescriptions and follow-up.'],
            'card_geriatric_title' => ['label' => 'Geriatric title', 'type' => 'text', 'default' => 'Geriatric Care'],
            'card_geriatric_body' => ['label' => 'Geriatric body', 'type' => 'textarea', 'default' => 'Long-term elderly companions, dementia-aware support, daily-living assistance and family reporting.'],
            'card_support_title' => ['label' => '24×7 title', 'type' => 'text', 'default' => '24×7 Support'],
            'card_support_body' => ['label' => '24×7 body', 'type' => 'textarea', 'default' => 'Doctor-on-call escalation, urgent visits, and continuous coordination with your hospital, if any.'],
        ],
    ]),

];
