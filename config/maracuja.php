<?php

return [
    'product_name' => env('MARACUJA_PRODUCT_NAME', 'Maracuja Digital'),

    'control' => [
        // Observation only for now: a site remains authoritative for its local
        // runtime flags until the policy distribution and rollback contract is
        // explicitly enabled in a later release.
        'policy_mode' => env('MARACUJA_CONTROL_POLICY_MODE', 'observe'),
        'policy_manifest_path' => env(
            'MARACUJA_CONTROL_POLICY_MANIFEST',
            base_path('maracuja-site.manifest.json'),
        ),
    ],

    'theme' => env('MARACUJA_THEME', 'default'),

    'offer' => env('MARACUJA_OFFER', 'signature'),

    'seo' => [
        'indexable' => env('MARACUJA_INDEXABLE', false),
    ],

    'gallery' => [
        'slug' => env('MARACUJA_GALLERY_SLUG', 'home'),
        'layout' => env('MARACUJA_GALLERY_LAYOUT', 'grid'),
        'lightbox' => env('MARACUJA_GALLERY_LIGHTBOX', true),
    ],

    'news' => [
        'default_duration_days' => env('MARACUJA_NEWS_DEFAULT_DURATION_DAYS', 30),
    ],

    'articles' => [
        'public_path' => env('MARACUJA_ARTICLES_PUBLIC_PATH', 'articles'),
    ],

    'events' => [
        'public_path' => env('MARACUJA_EVENTS_PUBLIC_PATH', 'evenements'),
    ],

    'media' => [
        'disk' => 'public',
        'images_directory' => 'media/images',
        'documents_directory' => 'media/documents',
        'videos_directory' => 'media/videos',
        'video_thumbnails_directory' => 'media/video-thumbnails',
        'originals_disk' => 'local',
        'originals_directory' => 'media/originals',
        'ffmpeg_binary' => env('MARACUJA_FFMPEG_BINARY', 'ffmpeg'),
        'video_thumbnail_second' => 1,
        'image_max_size_kb' => 25 * 1024,
        'image_max_pixels' => 36_000_000,
        'image_variant_widths' => [640, 960, 1440, 1920],
        'image_webp_quality' => 82,
        'document_max_size_kb' => 15 * 1024,
        'video_max_size_kb' => 100 * 1024,
        'mime_types' => [
            'image/jpeg' => ['type' => 'image', 'extension' => 'jpg'],
            'image/png' => ['type' => 'image', 'extension' => 'png'],
            'image/webp' => ['type' => 'image', 'extension' => 'webp'],
            'image/heic' => ['type' => 'image', 'extension' => 'heic'],
            'image/heif' => ['type' => 'image', 'extension' => 'heif'],
            'application/pdf' => ['type' => 'document', 'extension' => 'pdf'],
            'video/mp4' => ['type' => 'video', 'extension' => 'mp4'],
            'video/webm' => ['type' => 'video', 'extension' => 'webm'],
        ],
    ],

    // The runtime Assistant package owns its technical configuration. This
    // application only decides whether the capability is installed and active.

    'modules' => [
        'site_settings' => env('MARACUJA_MODULE_SITE_SETTINGS', true),
        'notices' => env('MARACUJA_MODULE_NOTICES', true),
        'content_slots' => env('MARACUJA_MODULE_CONTENT_SLOTS', true),
        'pages' => env('MARACUJA_MODULE_PAGES', true),
        'news' => env('MARACUJA_MODULE_NEWS', true),
        'articles' => env('MARACUJA_MODULE_ARTICLES', true),
        'venues' => env('MARACUJA_MODULE_VENUES', true),
        'events' => env('MARACUJA_MODULE_EVENTS', true),
        'gallery' => env('MARACUJA_MODULE_GALLERY', true),
        'contact_form' => env('MARACUJA_MODULE_CONTACT_FORM', true),
        'assistant' => env('MARACUJA_MODULE_ASSISTANT', true),
        'audience' => env('MARACUJA_MODULE_AUDIENCE', false),
        'campaigns' => env('MARACUJA_MODULE_CAMPAIGNS', false),
    ],

    'developer_tools' => [
        'pages_admin' => env('MARACUJA_DEV_PAGES_ADMIN', false),
        'image_optimization' => env('MARACUJA_DEV_IMAGE_OPTIMIZATION', false),
    ],

    'offers' => [
        'essence' => [
            'site_settings' => true,
            'notices' => false,
            'content_slots' => false,
            'pages' => true,
            'news' => false,
            'articles' => false,
            'venues' => false,
            'events' => false,
            'gallery' => false,
            'contact_form' => true,
            'audience' => false,
            'campaigns' => false,
        ],
        'signature' => [
            'site_settings' => true,
            'notices' => true,
            'content_slots' => true,
            'pages' => true,
            'news' => true,
            'articles' => true,
            'venues' => true,
            'events' => true,
            'gallery' => true,
            'contact_form' => true,
            'audience' => false,
            'campaigns' => false,
        ],
        'univers' => [
            'site_settings' => true,
            'notices' => true,
            'content_slots' => true,
            'pages' => true,
            'news' => true,
            'articles' => true,
            'venues' => true,
            'events' => true,
            'gallery' => true,
            'contact_form' => true,
            'audience' => true,
            'campaigns' => false,
        ],
    ],
];
