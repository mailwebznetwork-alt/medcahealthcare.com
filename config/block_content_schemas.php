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
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Brand Strategy · India, Ireland, UAE'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => "Most Businesses Don't Need More Marketing. They Need Greater Clarity."],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Brand strategy, market positioning, strategic websites, digital marketing, and business growth consulting for organizations that value long-term thinking.'],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call'],
            'secondary_cta_label' => ['label' => 'WhatsApp CTA label', 'type' => 'text', 'default' => 'WhatsApp Us'],
        ],
        'hero-contact' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Contact'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Talk to LetsSee.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => "Tell us about your business goals and we'll help you plan the next strategic step."],
        ],
        'hero-business-growth' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Premium Brand Strategy · India, Ireland, UAE'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Strategic solutions for businesses ready to grow.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Brand strategy, website systems, digital marketing, and growth consulting across India, Ireland, and UAE.'],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call now'],
            'secondary_cta_label' => ['label' => 'Secondary CTA label', 'type' => 'text', 'default' => 'View services'],
            'secondary_cta_url' => ['label' => 'Secondary CTA URL', 'type' => 'text', 'default' => '/services-catalog'],
        ],
        'hero-about' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'About LetsSee'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'We Build Businesses People Trust.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'LetsSee is a brand strategy, market positioning, and business growth consultancy for organizations that value strategy, quality, and long-term thinking.'],
        ],
        'cta-home' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => "Ready to become the preferred choice in your market?"],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => "Speak to LetsSee about clarity, positioning, trust, and sustainable business growth."],
            'primary_cta_label' => ['label' => 'Primary CTA label', 'type' => 'text', 'default' => 'Call'],
            'secondary_cta_label' => ['label' => 'Secondary CTA label', 'type' => 'text', 'default' => 'Request Callback'],
            'secondary_cta_url' => ['label' => 'Secondary CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'cta-banner' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Strategic consultation available'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Book now'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'cta-simple' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Ready to talk strategy?'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'text', 'default' => 'Simple CTA band for landing pages.'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Request callback'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'cta-services' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Not sure which service fits?'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'A LetsSee consultant will help you identify the right strategic next step for your business.'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Schedule A Strategy Consultation'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'contact-info' => [
            'call_title' => ['label' => 'Call card title', 'type' => 'text', 'default' => 'Call'],
            'call_body' => ['label' => 'Call card body', 'type' => 'text', 'default' => 'For strategy consultation and business growth enquiries.'],
            'whatsapp_title' => ['label' => 'WhatsApp card title', 'type' => 'text', 'default' => 'WhatsApp'],
            'whatsapp_body' => ['label' => 'WhatsApp card body', 'type' => 'text', 'default' => "Send a message and we'll respond fast."],
            'hours_title' => ['label' => 'Hours card title', 'type' => 'text', 'default' => 'Hours'],
            'hours_body' => ['label' => 'Hours card body', 'type' => 'text', 'default' => 'Call or WhatsApp for strategy consultation. Email: hello@letssee.in.'],
        ],
        'form-callback' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Request a callback'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Submit your details below. Our strategy team will respond as soon as possible.'],
            'primary_cta_label' => ['label' => 'CTA label', 'type' => 'text', 'default' => 'Go to contact form'],
            'primary_cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'default' => '/contact'],
        ],
        'hero-services' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Services'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Strategic solutions for businesses ready to grow.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Every LetsSee service is designed to strengthen business foundations, improve positioning, build trust, and support sustainable growth.'],
        ],
        'hero-locations' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Service Areas'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Countries & States We Serve'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'LetsSee supports consulting engagements across India, Ireland, and UAE.'],
        ],
        'contact-split' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Contact LetsSee'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'text', 'default' => 'Call, WhatsApp, or visit our contact page.'],
            'hours_line' => ['label' => 'Hours line', 'type' => 'text', 'default' => 'Email: hello@letssee.in'],
            'area_line' => ['label' => 'Area line', 'type' => 'text', 'default' => 'India, Ireland, UAE'],
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
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Strategic solutions designed to build stronger foundations for growth.'],
            'link_label' => ['label' => 'View all link', 'type' => 'text', 'default' => 'View all services →'],
            'link_url' => ['label' => 'View all URL', 'type' => 'text', 'default' => '/services'],
            'card_consulting_title' => ['label' => 'Card: Strategic Website & Growth Systems title', 'type' => 'text', 'default' => 'Brand Strategy & Positioning'],
            'card_consulting_body' => ['label' => 'Card: Strategic Website & Growth Systems body', 'type' => 'textarea', 'default' => 'Creating clarity, differentiation, and competitive advantage.'],
            'card_physio_title' => ['label' => 'Card: Physio title', 'type' => 'text', 'default' => 'Strategic Website & Growth Systems'],
            'card_physio_body' => ['label' => 'Card: Physio body', 'type' => 'textarea', 'default' => 'Building digital platforms designed to support visibility, trust, and growth.'],
            'card_diagnostics_title' => ['label' => 'Card: Diagnostics title', 'type' => 'text', 'default' => 'Digital Marketing & Lead Generation'],
            'card_diagnostics_body' => ['label' => 'Card: Diagnostics body', 'type' => 'textarea', 'default' => 'Creating opportunities through strategic communication and measurable marketing.'],
            'card_support_title' => ['label' => 'Card: Support title', 'type' => 'text', 'default' => 'Business Growth Consulting'],
            'card_support_body' => ['label' => 'Card: Support body', 'type' => 'textarea', 'default' => 'Helping businesses make better decisions and build sustainable growth systems.'],
        ],
        'locations-overview-home' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Countries'],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'National and international consulting coverage.'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'LetsSee supports businesses across India, Ireland, and UAE. No pincode selection is required.'],
            'link_label' => ['label' => 'Link label', 'type' => 'text', 'default' => 'View countries →'],
            'link_url' => ['label' => 'Link URL', 'type' => 'text', 'default' => '/locations'],
        ],
        'near-you-home' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Service Areas'],
            'headline_with_area' => ['label' => 'Headline (when country set; use :area)', 'type' => 'text', 'default' => 'Digital Growth Platform Services in :area'],
            'headline_no_country' => ['label' => 'Headline (no country)', 'type' => 'text', 'default' => 'digital growth services by country'],
            'country_line' => ['label' => 'Country line (use :pin)', 'type' => 'text', 'default' => 'Showing coverage for country :pin'],
            'change_country_label' => ['label' => 'Change Country button', 'type' => 'text', 'default' => 'Change Country'],
            'set_country_label' => ['label' => 'Select Country button', 'type' => 'text', 'default' => 'Select Country'],
            'location_required_message' => ['label' => 'Select country prompt', 'type' => 'textarea', 'default' => 'Select your country to see national and international services available for you.'],
            'empty_categories_message' => ['label' => 'No categories message', 'type' => 'textarea', 'default' => 'No published categories are mapped to this country yet.'],
            'empty_services_message' => ['label' => 'No categories message (legacy key)', 'type' => 'textarea', 'default' => 'No published categories are mapped to this country yet.'],
        ],
        'near-you-locations' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'Service Areas'],
            'headline_with_area' => ['label' => 'Headline (when country set; use :area)', 'type' => 'text', 'default' => 'Digital Growth Platform Services in :area'],
            'headline_no_country' => ['label' => 'Headline (no country)', 'type' => 'text', 'default' => 'digital growth platform services available by country'],
            'country_line' => ['label' => 'Country line (use :pin)', 'type' => 'text', 'default' => 'Showing coverage for country :pin'],
            'change_country_label' => ['label' => 'Change Country button', 'type' => 'text', 'default' => 'Change Country'],
            'set_country_label' => ['label' => 'Select Country button', 'type' => 'text', 'default' => 'Select Country'],
            'location_required_message' => ['label' => 'Select country prompt', 'type' => 'textarea', 'default' => 'National and international services available across India, Ireland, and UAE.'],
            'empty_categories_message' => ['label' => 'No categories message', 'type' => 'textarea', 'default' => 'No published categories are mapped to this country yet.'],
            'empty_services_message' => ['label' => 'No categories message (legacy key)', 'type' => 'textarea', 'default' => 'No published categories are mapped to this country yet.'],
        ],
        'body-about' => [
            'mission_title' => ['label' => 'Mission title', 'type' => 'text', 'default' => 'Our mission'],
            'mission_body' => ['label' => 'Mission body', 'type' => 'textarea', 'default' => 'Build the strategic foundations that help businesses become valuable before they become visible.'],
            'vision_title' => ['label' => 'Vision title', 'type' => 'text', 'default' => 'Our vision'],
            'vision_body' => ['label' => 'Vision body', 'type' => 'textarea', 'default' => 'A market where growth-focused businesses build trust through clarity, consistency, and quality.'],
            'model_title' => ['label' => 'Care model title', 'type' => 'text', 'default' => 'Our care model'],
            'model_body' => ['label' => 'Care model body', 'type' => 'textarea', 'default' => 'Every LetsSee engagement is guided by strategy, executed with focus, and aligned to measurable business outcomes.'],
            'trust_title' => ['label' => 'Trust title', 'type' => 'text', 'default' => 'Why businesses choose LetsSee'],
            'trust_bullets' => ['label' => 'Trust bullets (one per line)', 'type' => 'textarea', 'default' => "Expert-led care plans — not just task-based visits.\nVerified, trained clinicians with regular audits.\nTransparent pricing and clear escalation paths.\nTight 25 km service belt for fast, reliable response."],
        ],
        'locations-coverage' => [
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Countries & States We Serve'],
            'footnote' => ['label' => 'Footnote', 'type' => 'text', 'default' => "No pincode selection is required. LetsSee works with businesses across India, Ireland, and UAE."],
        ],
        'hero-careers' => [
            'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => ''],
            'headline' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Careers at LetsSee'],
            'subheadline' => ['label' => 'Subheadline', 'type' => 'textarea', 'default' => 'Join LetsSee across strategy, marketing, operations, and growth roles.'],
        ],
        'services-grid-full' => [
            'card_consulting_title' => ['label' => 'Strategic Website & Growth Systems title', 'type' => 'text', 'default' => 'Brand Strategy & Positioning'],
            'card_consulting_body' => ['label' => 'Strategic Website & Growth Systems body', 'type' => 'textarea', 'default' => 'Build the foundation before you build the brand.'],
            'card_physio_title' => ['label' => 'Physio title', 'type' => 'text', 'default' => 'Strategic Website & Growth Systems'],
            'card_physio_body' => ['label' => 'Physio body', 'type' => 'textarea', 'default' => 'More than a website: a business growth platform.'],
            'card_diagnostics_title' => ['label' => 'Diagnostics title', 'type' => 'text', 'default' => 'Digital Marketing & Lead Generation'],
            'card_diagnostics_body' => ['label' => 'Diagnostics body', 'type' => 'textarea', 'default' => 'Visibility that creates business opportunities.'],
            'card_doctor_title' => ['label' => 'Business growth title', 'type' => 'text', 'default' => 'Business Growth Consulting'],
            'card_doctor_body' => ['label' => 'Business growth body', 'type' => 'textarea', 'default' => 'Strategic thinking for businesses ready to scale.'],
            'card_geriatric_title' => ['label' => 'Geriatric title', 'type' => 'text', 'default' => 'Strategy First'],
            'card_geriatric_body' => ['label' => 'Geriatric body', 'type' => 'textarea', 'default' => 'Clarity and positioning before execution.'],
            'card_support_title' => ['label' => '24×7 title', 'type' => 'text', 'default' => 'Growth With Purpose'],
            'card_support_body' => ['label' => '24×7 body', 'type' => 'textarea', 'default' => 'Sustainable systems for long-term business growth.'],
        ],
    ]),

];
