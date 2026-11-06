<?php

return [
    'enabled' => (bool) env('CREMONA_INCOMING_REQUESTS_ENABLED', false),
    'endpoint' => rtrim((string) env('CREMONA_INCOMING_REQUESTS_ENDPOINT', 'https://cremona.maracujadigital.fr/api/v1/incoming-requests'), '/'),
    'token' => env('CREMONA_INCOMING_REQUESTS_TOKEN'),
    'site_reference' => env('CREMONA_INCOMING_REQUESTS_SITE_REFERENCE'),
    'source_name' => env('CREMONA_INCOMING_REQUESTS_SOURCE_NAME', 'site-web'),
];
