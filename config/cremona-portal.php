<?php

return [
    // The site is an OAuth public client. PKCE binds each code exchange to
    // the browser session, so no shared secret is stored in a client site.
    'enabled' => (bool) env('CREMONA_SSO_ENABLED', false),
    'issuer' => rtrim((string) env('CREMONA_SSO_ISSUER', 'https://cremona.maracujadigital.fr'), '/'),
    'client_id' => env('CREMONA_SSO_CLIENT_ID'),
    'logout_secret' => env('CREMONA_SSO_LOGOUT_SECRET'),
    'redirect_uri' => env('CREMONA_SSO_REDIRECT_URI', rtrim((string) env('APP_URL'), '/').'/admin/sso/callback'),

    // This is an explicit pilot choice. A reusable rollout will map local site
    // roles deliberately instead of deriving them from business permissions.
    'default_role' => env('CREMONA_SSO_DEFAULT_SITE_ROLE', 'admin'),
];
