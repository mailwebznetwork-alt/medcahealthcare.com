<?php

return [

    'session_key' => 'medca.detected_country',

    'ip_attempted_session_key' => 'medca.location_ip_attempted',

    'geo_attempted_session_key' => 'medca.location_geo_attempted',

    /** Fallback when IP / browser geo cannot resolve a serviceable country. */
    'default_country' => env('MEDCA_DEFAULT_PINCODE', '560076'),

    /** ip-api.com fields (free tier, no key). */
    'ip_lookup_url' => env('LOCATION_IP_LOOKUP_URL', 'http://ip-api.com/json/{ip}?fields=status,zip,countryCode,city'),

    'ip_lookup_timeout' => (int) env('LOCATION_IP_LOOKUP_TIMEOUT', 4),

    /** OpenStreetMap Nominatim reverse geocode (no key; be polite with timeout). */
    'reverse_geocode_url' => env('LOCATION_REVERSE_GEOCODE_URL', 'https://nominatim.openstreetmap.org/reverse'),

];
