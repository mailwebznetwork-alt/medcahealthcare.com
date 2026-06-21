<?php

/**
 * Global content variable definitions.
 * Values stored in global_content_variables; identity fields fall back to theme branding / medca config.
 */
return [

    'groups' => [
        'identity' => [
            'label' => 'Identity & contact',
            'description' => 'Shared business constants for header, footer, and tokens.',
        ],
        'brand_story' => [
            'label' => 'Brand story',
            'description' => 'Mission, vision, care model, and trust narrative for About and Home.',
        ],
        'home' => [
            'label' => 'Home page',
            'description' => 'Default hero and value copy for the home page.',
        ],
        'contact' => [
            'label' => 'Contact page',
            'description' => 'Contact hero, hours, and location lines.',
        ],
    ],

    'keys' => [
        'company_name' => [
            'label' => 'Company name',
            'group' => 'identity',
            'type' => 'text',
            'branding_key' => 'brand_name',
            'medca_key' => 'brand_name',
        ],
        'tagline' => [
            'label' => 'Tagline',
            'group' => 'identity',
            'type' => 'text',
            'branding_key' => 'tagline',
            'medca_key' => 'tagline',
        ],
        'phone_number' => [
            'label' => 'Phone number (display)',
            'group' => 'identity',
            'type' => 'text',
            'branding_key' => 'phone_display',
            'medca_key' => 'phone_display',
        ],
        'phone_tel' => [
            'label' => 'Phone (tel link)',
            'group' => 'identity',
            'type' => 'text',
            'branding_key' => 'phone_tel',
            'medca_key' => 'phone_tel',
        ],
        'whatsapp' => [
            'label' => 'WhatsApp URL',
            'group' => 'identity',
            'type' => 'text',
            'branding_key' => 'whatsapp_url',
            'medca_key' => 'whatsapp_url',
        ],
        'email' => [
            'label' => 'Email',
            'group' => 'identity',
            'type' => 'email',
            'branding_key' => 'contact_email',
            'medca_key' => 'contact_email',
        ],
        'address' => [
            'label' => 'Address',
            'group' => 'identity',
            'type' => 'textarea',
            'branding_key' => 'address',
            'medca_key' => 'location_display',
        ],
        'primary_cta' => [
            'label' => 'Primary CTA',
            'group' => 'identity',
            'type' => 'text',
            'branding_key' => 'primary_cta_text',
            'medca_key' => 'primary_cta_text',
        ],
        'secondary_cta' => [
            'label' => 'Secondary CTA',
            'group' => 'identity',
            'type' => 'text',
            'branding_key' => 'secondary_cta_text',
            'medca_key' => null,
        ],
        'business_hours' => [
            'label' => 'Business hours',
            'group' => 'identity',
            'type' => 'textarea',
            'branding_key' => 'business_hours',
            'medca_key' => null,
        ],
        'website_url' => [
            'label' => 'Website URL',
            'group' => 'identity',
            'type' => 'url',
            'branding_key' => 'brand_url',
            'medca_key' => null,
        ],
        'company_description_short' => [
            'label' => 'Short description',
            'group' => 'brand_story',
            'type' => 'textarea',
            'hint' => 'One or two sentences for home hero, meta, and footers.',
        ],
        'company_description_long' => [
            'label' => 'Long description',
            'group' => 'brand_story',
            'type' => 'textarea',
            'hint' => 'About page intro and extended brand narrative.',
        ],
        'mission_title' => [
            'label' => 'Mission title',
            'group' => 'brand_story',
            'type' => 'text',
        ],
        'mission_statement' => [
            'label' => 'Mission',
            'group' => 'brand_story',
            'type' => 'textarea',
        ],
        'vision_title' => [
            'label' => 'Vision title',
            'group' => 'brand_story',
            'type' => 'text',
        ],
        'vision_statement' => [
            'label' => 'Vision',
            'group' => 'brand_story',
            'type' => 'textarea',
        ],
        'care_model_title' => [
            'label' => 'Care model title',
            'group' => 'brand_story',
            'type' => 'text',
        ],
        'care_model' => [
            'label' => 'Care model (core model)',
            'group' => 'brand_story',
            'type' => 'textarea',
        ],
        'core_values' => [
            'label' => 'Core values (one per line)',
            'group' => 'brand_story',
            'type' => 'textarea',
        ],
        'why_choose_us' => [
            'label' => 'Why choose us (one per line)',
            'group' => 'brand_story',
            'type' => 'textarea',
        ],
        'trust_title' => [
            'label' => 'Trust section title',
            'group' => 'brand_story',
            'type' => 'text',
        ],
        'trust_pillars' => [
            'label' => 'Trust pillars (one per line)',
            'group' => 'brand_story',
            'type' => 'textarea',
        ],
        'service_area_summary' => [
            'label' => 'Service area summary',
            'group' => 'brand_story',
            'type' => 'textarea',
            'hint' => 'e.g. diagnostics network across Karnataka, Bangalore.',
        ],
        'founded_year' => [
            'label' => 'Founded year',
            'group' => 'brand_story',
            'type' => 'text',
        ],
        'home_hero_eyebrow' => [
            'label' => 'Home hero eyebrow',
            'group' => 'home',
            'type' => 'text',
        ],
        'home_hero_headline' => [
            'label' => 'Home hero headline',
            'group' => 'home',
            'type' => 'text',
        ],
        'home_hero_subheadline' => [
            'label' => 'Home hero subheadline',
            'group' => 'home',
            'type' => 'textarea',
        ],
        'contact_hero_eyebrow' => [
            'label' => 'Contact hero eyebrow',
            'group' => 'contact',
            'type' => 'text',
        ],
        'contact_hero_headline' => [
            'label' => 'Contact hero headline',
            'group' => 'contact',
            'type' => 'text',
        ],
        'contact_hero_subheadline' => [
            'label' => 'Contact hero subheadline',
            'group' => 'contact',
            'type' => 'textarea',
        ],
        'emergency_phone' => [
            'label' => 'Emergency / 24×7 phone (display)',
            'group' => 'contact',
            'type' => 'text',
        ],
        'map_url' => [
            'label' => 'Google Maps URL',
            'group' => 'contact',
            'type' => 'url',
        ],
        'response_time_promise' => [
            'label' => 'Response time promise',
            'group' => 'contact',
            'type' => 'text',
        ],
        'city' => [
            'label' => 'City',
            'group' => 'contact',
            'type' => 'text',
        ],
        'pincode' => [
            'label' => 'PIN code',
            'group' => 'contact',
            'type' => 'text',
        ],
        'about_hero_eyebrow' => [
            'label' => 'About hero eyebrow',
            'group' => 'brand_story',
            'type' => 'text',
        ],
        'about_hero_headline' => [
            'label' => 'About hero headline',
            'group' => 'brand_story',
            'type' => 'text',
        ],
        'about_hero_subheadline' => [
            'label' => 'About hero subheadline',
            'group' => 'brand_story',
            'type' => 'textarea',
        ],
    ],

];
