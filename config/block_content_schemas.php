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
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Medical Laboratory · Karnataka'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Accurate medical laboratory services, built for Karnataka.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Reliable lab testing, health packages, sample collection support, and patient-friendly diagnostic reporting.'],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call'],
            'secondary_cta_label' => ['label' => 'WhatsApp CTA label', 'type' => 'text', 'default' => 'WhatsApp Us'],
        ],
        'hero-contact' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Contact'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Talk to a Karnataka Diagnostic Centre coordinator.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Tell us which lab test or health package you need and our team will guide you.'],
        ],
        'hero-healthcare' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Medical Laboratory · Karnataka'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Laboratory-grade diagnostics for everyday health needs'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Laboratory testing, health packages, and diagnostic support across Karnataka.'],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call now'],
            'secondary_cta_label' => ['label' => 'Secondary CTA label', 'type' => 'text', 'default' => 'View services'],
            'secondary_cta_url' => ['label' => 'Secondary CTA URL', 'type' => 'text', 'default' => '/services-catalog'],
        ],
        'hero-about' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'About Karnataka Diagnostic Centre'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Trusted medical laboratory and diagnostic services.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Karnataka Diagnostic Centre is a medical laboratory and diagnostics brand focused on accurate testing, transparent reporting, and patient-friendly service.'],
        ],
        'cta-home' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Need lab test guidance? We are a call away.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => "Speak to our diagnostics coordinator for test details, packages, and sample collection support."],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call'],
            'secondary_cta_label' => ['label' => 'Secondary CTA label', 'type' => 'text', 'default' => 'Request Callback'],
            'secondary_cta_url' => ['label' => 'Secondary CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'cta-banner' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Banner CTA — lab test support available'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Book now'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'cta-simple' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Ready to talk to a diagnostics advisor?'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'text', 'default' => 'Simple CTA band for landing pages.'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Request callback'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'cta-services' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Not sure which service fits?'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Our diagnostics team will help you choose the right tests or health package for your needs.'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Talk to an advisor'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'contact-info' => [
            'call_title' => ['label' => 'Call card title', 'type' => 'text', 'default' => 'Call'],
            'call_body' => ['label' => 'Call card body', 'type' => 'text', 'default' => 'Call 088849 94222 for lab tests, health packages, and diagnostics guidance.'],
            'whatsapp_title' => ['label' => 'WhatsApp card title', 'type' => 'text', 'default' => 'WhatsApp'],
            'whatsapp_body' => ['label' => 'WhatsApp card body', 'type' => 'text', 'default' => "Send a message and we'll respond fast."],
            'hours_title' => ['label' => 'Hours card title', 'type' => 'text', 'default' => 'Hours'],
            'hours_body' => ['label' => 'Hours card body', 'type' => 'text', 'default' => 'Open until 7 pm'],
        ],
        'form-callback' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Request a callback'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Submit your details below. Our diagnostics team responds quickly.'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Go to contact form'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'hero-services' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Services'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Medical Laboratory services for everyday health needs.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'From routine blood tests to preventive health packages, every service is designed around accuracy, clarity, and timely reporting.'],
        ],
        'hero-locations' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Service Areas'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Where Karnataka Diagnostic Centre serves.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'A focused diagnostics network helps us keep service quality, reporting clarity, and patient support consistent.'],
        ],
        'contact-split' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Contact Karnataka Diagnostic Centre'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'text', 'default' => 'Call, WhatsApp, or visit our contact page.'],
            'hours_line' => ['label' => 'Hours line', 'type' => 'text', 'default' => 'Hours: 24×7 clinical line'],
            'area_line' => ['label' => 'Area line', 'type' => 'text', 'default' => 'Karnataka'],
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
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Diagnostic services for everyday health needs.'],
            'link_label' => ['label' => 'View all link', 'type' => 'text', 'default' => 'View all services →'],
            'link_url' => ['label' => 'View all URL', 'type' => 'text', 'default' => '/services'],
            'card_nursing_title' => ['label' => 'Card: Lab test title', 'type' => 'text', 'default' => 'Lab Tests'],
            'card_nursing_body' => ['label' => 'Card: Lab test body', 'type' => 'textarea', 'default' => 'Routine blood tests, health profiles, and preventive screening support.'],
            'card_physio_title' => ['label' => 'Card: Health packages title', 'type' => 'text', 'default' => 'Health Packages'],
            'card_physio_body' => ['label' => 'Card: Health packages body', 'type' => 'textarea', 'default' => 'Preventive health checkup packages with clear test information.'],
            'card_diagnostics_title' => ['label' => 'Card: Specialized profiles title', 'type' => 'text', 'default' => 'Specialized Profiles'],
            'card_diagnostics_body' => ['label' => 'Card: Specialized profiles body', 'type' => 'textarea', 'default' => 'Cardiac, diabetes, kidney, liver, thyroid, and vitamin profile testing.'],
            'card_support_title' => ['label' => 'Card: Support title', 'type' => 'text', 'default' => 'Diagnostic Support'],
            'card_support_body' => ['label' => 'Card: Support body', 'type' => 'textarea', 'default' => 'Test guidance, sample collection support, and report coordination.'],
        ],
        'locations-overview-home' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Service Belt'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Medical laboratory services across Karnataka.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'We are preparing diagnostics coverage for key Karnataka locations with clear test information and patient-friendly access.'],
            'link_label' => ['label' => 'Link label', 'type' => 'text', 'default' => 'See all coverage areas →'],
            'link_url' => ['label' => 'Link URL', 'type' => 'text', 'default' => '/locations'],
        ],
        'near-you-home' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Near You'],
            'headline_with_area' => ['label' => 'Headline (when pincode set; use :area)', 'type' => 'text', 'default' => 'Medical Laboratory Services in :area'],
            'headline_no_pincode' => ['label' => 'Headline (no pincode)', 'type' => 'text', 'default' => 'Medical laboratory categories near your pincode'],
            'pincode_line' => ['label' => 'Pincode line (use :pin)', 'type' => 'text', 'default' => 'Showing coverage for pincode :pin'],
            'change_pincode_label' => ['label' => 'Change Pincode button', 'type' => 'text', 'default' => 'Change Pincode'],
            'set_pincode_label' => ['label' => 'Set Pincode button', 'type' => 'text', 'default' => 'Set Pincode'],
            'location_required_message' => ['label' => 'Set pincode prompt', 'type' => 'textarea', 'default' => 'Set your pincode to see which diagnostic services are available in your area.'],
            'empty_categories_message' => ['label' => 'No categories message', 'type' => 'textarea', 'default' => 'No published categories are mapped to this pincode yet.'],
            'empty_services_message' => ['label' => 'No categories message (legacy key)', 'type' => 'textarea', 'default' => 'No published categories are mapped to this pincode yet.'],
        ],
        'near-you-locations' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Near You'],
            'headline_with_area' => ['label' => 'Headline (when pincode set; use :area)', 'type' => 'text', 'default' => 'Medical Laboratory Services in :area'],
            'headline_no_pincode' => ['label' => 'Headline (no pincode)', 'type' => 'text', 'default' => 'Medical laboratory categories available near you'],
            'pincode_line' => ['label' => 'Pincode line (use :pin)', 'type' => 'text', 'default' => 'Showing coverage for pincode :pin'],
            'change_pincode_label' => ['label' => 'Change Pincode button', 'type' => 'text', 'default' => 'Change Pincode'],
            'set_pincode_label' => ['label' => 'Set Pincode button', 'type' => 'text', 'default' => 'Set Pincode'],
            'location_required_message' => ['label' => 'Set pincode prompt', 'type' => 'textarea', 'default' => 'Set your pincode to see which diagnostic services are available in your area.'],
            'empty_categories_message' => ['label' => 'No categories message', 'type' => 'textarea', 'default' => 'No published categories are mapped to this pincode yet.'],
            'empty_services_message' => ['label' => 'No categories message (legacy key)', 'type' => 'textarea', 'default' => 'No published categories are mapped to this pincode yet.'],
        ],
        'body-about' => [
            'mission_title' => ['label' => 'Mission title', 'type' => 'text', 'default' => 'Our mission'],
            'mission_body' => ['label' => 'Mission body', 'type' => 'textarea', 'default' => 'Make accurate diagnostic testing easier to understand, access, and act on for families across Karnataka.'],
            'vision_title' => ['label' => 'Vision title', 'type' => 'text', 'default' => 'Our vision'],
            'vision_body' => ['label' => 'Vision body', 'type' => 'textarea', 'default' => 'A Karnataka where every family can access reliable Medical Laboratory services with clear reporting and timely support.'],
            'model_title' => ['label' => 'Care model title', 'type' => 'text', 'default' => 'Our diagnostics model'],
            'model_body' => ['label' => 'Care model body', 'type' => 'textarea', 'default' => 'Every diagnostics workflow is built around test accuracy, clear reporting, and accountable patient support.'],
            'trust_title' => ['label' => 'Trust title', 'type' => 'text', 'default' => 'Why Karnataka families trust us'],
            'trust_bullets' => ['label' => 'Trust bullets (one per line)', 'type' => 'textarea', 'default' => "Clear test information before booking.\nReliable sample collection and lab coordination.\nTransparent pricing and report guidance.\nPatient-friendly diagnostic support."],
        ],
        'locations-coverage' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Areas We Serve'],
            'footnote' => ['label' => 'Footnote', 'type' => 'text', 'default' => "Don't see your locality? Call us for current diagnostic service availability."],
        ],
        'hero-careers' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => ''],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Careers at Karnataka Diagnostic Centre'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Join our laboratory, sample collection, reporting, and operations teams.'],
        ],
        'services-grid-full' => [
            'card_nursing_title' => ['label' => 'Lab test title', 'type' => 'text', 'default' => 'Lab Tests'],
            'card_nursing_body' => ['label' => 'Lab test body', 'type' => 'textarea', 'default' => 'Routine blood tests, health profiles, and preventive screening support.'],
            'card_physio_title' => ['label' => 'Health packages title', 'type' => 'text', 'default' => 'Health Packages'],
            'card_physio_body' => ['label' => 'Health packages body', 'type' => 'textarea', 'default' => 'Preventive health checkup packages with clear test information.'],
            'card_diagnostics_title' => ['label' => 'Specialized profiles title', 'type' => 'text', 'default' => 'Specialized Profiles'],
            'card_diagnostics_body' => ['label' => 'Specialized profiles body', 'type' => 'textarea', 'default' => 'Cardiac, diabetes, kidney, liver, thyroid, and vitamin profile testing.'],
            'card_doctor_title' => ['label' => 'Sample collection title', 'type' => 'text', 'default' => 'Sample Collection Support'],
            'card_doctor_body' => ['label' => 'Sample collection body', 'type' => 'textarea', 'default' => 'Patient-friendly guidance for test preparation and sample collection.'],
            'card_geriatric_title' => ['label' => 'Preventive screening title', 'type' => 'text', 'default' => 'Preventive Screening'],
            'card_geriatric_body' => ['label' => 'Preventive screening body', 'type' => 'textarea', 'default' => 'Screening support for families, seniors, and routine wellness tracking.'],
            'card_support_title' => ['label' => 'Report coordination title', 'type' => 'text', 'default' => 'Report Coordination'],
            'card_support_body' => ['label' => 'Report coordination body', 'type' => 'textarea', 'default' => 'Clear reporting support and diagnostic follow-up guidance.'],
        ],
    ]),

];
