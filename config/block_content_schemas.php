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
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Comprehensive Home Healthcare Services'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Trusted Home Healthcare Services in Bangalore'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'At Medca Healthcare, we provide professional home healthcare solutions designed to improve comfort, safety, recovery, and quality of life.'],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call Us'],
            'secondary_cta_label' => ['label' => 'WhatsApp CTA label', 'type' => 'text', 'default' => 'WhatsApp Us'],
            'secondary_cta_url' => ['label' => 'WhatsApp CTA URL (leave empty for default)', 'type' => 'text', 'default' => ''],
        ],
        'hero-contact' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Contact'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Talk to a Medca Healthcare care advisor.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Tell us what support you need at home and our care team will guide you to the right service.'],
        ],
        'hero-healthcare' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Home Healthcare · Bangalore'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Hospital-grade care, delivered at home'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Nursing, elder care, physiotherapy, doctor visits, and recovery support across Bangalore.'],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call now'],
            'secondary_cta_label' => ['label' => 'Secondary CTA label', 'type' => 'text', 'default' => 'View services'],
            'secondary_cta_url' => ['label' => 'Secondary CTA URL', 'type' => 'text', 'default' => '/services-catalog'],
        ],
        'hero-about' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'About Medca Healthcare'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Trusted doctor-led home healthcare services.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Medca Healthcare is a home healthcare brand focused on safe recovery, dignified elder care, and dependable clinical support at home.'],
        ],
        'cta-home' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Need home healthcare guidance? We are a call away.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Speak to our care coordinator for nursing, elder care, physiotherapy, and recovery support at home.'],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call Us'],
            'secondary_cta_label' => ['label' => 'WhatsApp CTA label', 'type' => 'text', 'default' => 'WhatsApp Us'],
            'secondary_cta_url' => ['label' => 'WhatsApp CTA URL (leave empty for default)', 'type' => 'text', 'default' => ''],
        ],
        'cta-banner' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Banner CTA — home healthcare support available'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Book now'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'cta-simple' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Ready to talk to a home healthcare advisor?'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'text', 'default' => 'Simple CTA band for landing pages.'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Request callback'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'cta-services' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Not sure which service fits?'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Our care team will help you choose the right home healthcare service for your needs.'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Talk to an advisor'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'contact-info' => [
            'call_title' => ['label' => 'Call card title', 'type' => 'text', 'default' => 'Call'],
            'call_body' => ['label' => 'Call card body', 'type' => 'text', 'default' => 'Call +91 8884 999 002 for home nursing, elder care, physiotherapy, and care coordination.'],
            'whatsapp_title' => ['label' => 'WhatsApp card title', 'type' => 'text', 'default' => 'WhatsApp'],
            'whatsapp_body' => ['label' => 'WhatsApp card body', 'type' => 'text', 'default' => "Send a message and we'll respond fast."],
            'hours_title' => ['label' => 'Hours card title', 'type' => 'text', 'default' => 'Hours'],
            'hours_body' => ['label' => 'Hours card body', 'type' => 'text', 'default' => 'Open until 7 pm'],
        ],
        'form-callback' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Request a callback'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Submit your details below. Our care team responds quickly.'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Go to contact form'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'hero-services' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Services'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Home healthcare services for everyday recovery and support.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'From nursing and elder care to physiotherapy and doctor visits, every service is designed around safety, dignity, and recovery at home.'],
        ],
        'hero-locations' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Service Areas'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Where Medca Healthcare serves.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'A focused home healthcare network helps us keep care quality, response speed, and family support consistent across Bangalore.'],
        ],
        'contact-split' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Contact Medca Healthcare'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'text', 'default' => 'Call, WhatsApp, or visit our contact page.'],
            'hours_line' => ['label' => 'Hours line', 'type' => 'text', 'default' => 'Hours: 24×7 clinical line'],
            'area_line' => ['label' => 'Area line', 'type' => 'text', 'default' => 'Bangalore'],
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
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Home healthcare services for everyday recovery and support.'],
            'link_label' => ['label' => 'View all link', 'type' => 'text', 'default' => 'View all services →'],
            'link_url' => ['label' => 'View all URL', 'type' => 'text', 'default' => '/services'],
            'card_nursing_title' => ['label' => 'Card: Home nursing title', 'type' => 'text', 'default' => 'Home Nursing'],
            'card_nursing_body' => ['label' => 'Card: Home nursing body', 'type' => 'textarea', 'default' => 'Skilled nursing, wound care, and post-operative recovery with doctor oversight.'],
            'card_physio_title' => ['label' => 'Card: Physiotherapy title', 'type' => 'text', 'default' => 'Physiotherapy'],
            'card_physio_body' => ['label' => 'Card: Physiotherapy body', 'type' => 'textarea', 'default' => 'Stroke, orthopaedic, and pain rehabilitation with licensed therapists at home.'],
            'card_diagnostics_title' => ['label' => 'Card: Specialized care title', 'type' => 'text', 'default' => 'ICU / Specialized Care'],
            'card_diagnostics_body' => ['label' => 'Card: Specialized care body', 'type' => 'textarea', 'default' => 'Critical-care nursing and monitoring at home after clinical feasibility review.'],
            'card_support_title' => ['label' => 'Card: Support title', 'type' => 'text', 'default' => 'Elder & Caregiver Support'],
            'card_support_body' => ['label' => 'Card: Support body', 'type' => 'textarea', 'default' => 'Elder care and trained caregivers with nursing escalation when needed.'],
        ],
        'locations-overview-home' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Service Belt'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Home healthcare services across Bangalore.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'We provide focused home healthcare coverage across key Bangalore neighbourhoods with dependable clinical response and family support.'],
            'link_label' => ['label' => 'Link label', 'type' => 'text', 'default' => 'See all coverage areas →'],
            'link_url' => ['label' => 'Link URL', 'type' => 'text', 'default' => '/locations'],
        ],
        'near-you-home' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Near You'],
            'headline_with_area' => ['label' => 'Headline (when pincode set; use :area)', 'type' => 'text', 'default' => 'Home Healthcare Services in :area'],
            'headline_no_pincode' => ['label' => 'Headline (no pincode)', 'type' => 'text', 'default' => 'Home healthcare services near your pincode'],
            'pincode_line' => ['label' => 'Pincode line (use :pin)', 'type' => 'text', 'default' => 'Showing coverage for pincode :pin'],
            'change_pincode_label' => ['label' => 'Change Pincode button', 'type' => 'text', 'default' => 'Change Pincode'],
            'set_pincode_label' => ['label' => 'Set Pincode button', 'type' => 'text', 'default' => 'Set Pincode'],
            'location_required_message' => ['label' => 'Set pincode prompt', 'type' => 'textarea', 'default' => 'Set your pincode to see which home healthcare services are available in your area.'],
            'empty_categories_message' => ['label' => 'No categories message', 'type' => 'textarea', 'default' => 'No published categories are mapped to this pincode yet.'],
            'empty_services_message' => ['label' => 'No categories message (legacy key)', 'type' => 'textarea', 'default' => 'No published categories are mapped to this pincode yet.'],
        ],
        'near-you-locations' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Near You'],
            'headline_with_area' => ['label' => 'Headline (when pincode set; use :area)', 'type' => 'text', 'default' => 'Home Healthcare Services in :area'],
            'headline_no_pincode' => ['label' => 'Headline (no pincode)', 'type' => 'text', 'default' => 'Home healthcare services available near you'],
            'pincode_line' => ['label' => 'Pincode line (use :pin)', 'type' => 'text', 'default' => 'Showing coverage for pincode :pin'],
            'change_pincode_label' => ['label' => 'Change Pincode button', 'type' => 'text', 'default' => 'Change Pincode'],
            'set_pincode_label' => ['label' => 'Set Pincode button', 'type' => 'text', 'default' => 'Set Pincode'],
            'location_required_message' => ['label' => 'Set pincode prompt', 'type' => 'textarea', 'default' => 'Set your pincode to see which home healthcare services are available in your area.'],
            'empty_categories_message' => ['label' => 'No categories message', 'type' => 'textarea', 'default' => 'No published categories are mapped to this pincode yet.'],
            'empty_services_message' => ['label' => 'No categories message (legacy key)', 'type' => 'textarea', 'default' => 'No published categories are mapped to this pincode yet.'],
        ],
        'body-about' => [
            'trust_title' => ['label' => 'Why choose headline', 'type' => 'text', 'default' => 'Why Choose Medca?'],
            'trust_bullets' => ['label' => 'Trust pillars (Title|Body, one per line)', 'type' => 'textarea', 'default' => "Verified Professionalism|Every member of our team undergoes rigorous background checks and clinical competency training.\nPersonalized Care Blueprints|We create a customized care plan that evolves with the patient's recovery and specific needs.\nUnwavering Reliability|From timely medication administration to 24/7 bedside support, we ensure your loved one is never left without attention.\nCompassionate Connection|We believe care is more than clinical tasks—it is about dignity, companionship, and improving quality of life every day."],
            'promise_headline' => ['label' => 'Quality promise headline', 'type' => 'text', 'default' => 'The Medca Quality Promise'],
            'promise_intro' => ['label' => 'Quality promise intro', 'type' => 'textarea', 'default' => 'We hold ourselves to a standard higher than the industry average. Our commitment to you:'],
            'mission_title' => ['label' => 'Promise 1 title', 'type' => 'text', 'default' => 'Clinical Supervision'],
            'mission_body' => ['label' => 'Promise 1 body', 'type' => 'textarea', 'default' => 'Our caregivers are guided by senior nursing supervisors to ensure quality is maintained 24/7.'],
            'vision_title' => ['label' => 'Promise 2 title', 'type' => 'text', 'default' => 'Transparent Communication'],
            'vision_body' => ['label' => 'Promise 2 body', 'type' => 'textarea', 'default' => 'We maintain open channels with families, providing regular updates on the patient\'s health and progress.'],
            'model_title' => ['label' => 'Promise 3 title', 'type' => 'text', 'default' => 'Ethical Care Standards'],
            'model_body' => ['label' => 'Promise 3 body', 'type' => 'textarea', 'default' => 'We operate with complete integrity, ensuring every service provided is necessary, transparent, and focused purely on patient recovery.'],
        ],
        'locations-coverage' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Areas We Serve'],
            'footnote' => ['label' => 'Footnote', 'type' => 'text', 'default' => "Don't see your locality? Call us for current home healthcare service availability."],
        ],
        'hero-careers' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => ''],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Careers at Medca Healthcare'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Join our nursing, elder care, physiotherapy, and home healthcare operations teams.'],
        ],
        'services-grid-full' => [
            'card_nursing_title' => ['label' => 'Home nursing title', 'type' => 'text', 'default' => 'Home Nursing'],
            'card_nursing_body' => ['label' => 'Home nursing body', 'type' => 'textarea', 'default' => 'Skilled nursing, wound care, and post-operative recovery with doctor oversight.'],
            'card_physio_title' => ['label' => 'Physiotherapy title', 'type' => 'text', 'default' => 'Physiotherapy'],
            'card_physio_body' => ['label' => 'Physiotherapy body', 'type' => 'textarea', 'default' => 'Stroke, orthopaedic, and pain rehabilitation with licensed therapists at home.'],
            'card_diagnostics_title' => ['label' => 'Specialized care title', 'type' => 'text', 'default' => 'ICU / Specialized Care'],
            'card_diagnostics_body' => ['label' => 'Specialized care body', 'type' => 'textarea', 'default' => 'Critical-care nursing and monitoring at home after clinical feasibility review.'],
            'card_doctor_title' => ['label' => 'Doctor visits title', 'type' => 'text', 'default' => 'Doctor Home Visits'],
            'card_doctor_body' => ['label' => 'Doctor visits body', 'type' => 'textarea', 'default' => 'Doctor consultations and clinical review in the comfort of your home.'],
            'card_geriatric_title' => ['label' => 'Elder care title', 'type' => 'text', 'default' => 'Elder Care'],
            'card_geriatric_body' => ['label' => 'Elder care body', 'type' => 'textarea', 'default' => 'Dignified elder support with trained caregivers and nursing escalation when needed.'],
            'card_support_title' => ['label' => 'Care coordination title', 'type' => 'text', 'default' => 'Care Coordination'],
            'card_support_body' => ['label' => 'Care coordination body', 'type' => 'textarea', 'default' => 'Clear care planning, family updates, and recovery support at home.'],
        ],
    ]),

];
