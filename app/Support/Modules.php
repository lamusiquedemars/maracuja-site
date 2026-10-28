<?php

namespace App\Support;

class Modules
{
    public static function enabled(string $module): bool
    {
        $module = match ($module) {
            'contact' => 'contact_form',
            default => $module,
        };

        if (! self::installed($module)) {
            return false;
        }

        // Brevo is an optional technical adapter of the Audience capability.
        // It can never be exposed independently from Audience, even when the
        // package is present and its own capability flag is authorised.
        if ($module === 'brevo' && ! self::enabled('audience')) {
            return false;
        }

        return (bool) config("maracuja.modules.{$module}", false);
    }

    public static function installed(string $module): bool
    {
        $paths = [
            'site_settings' => app_path('Modules/SiteSettings'),
            'notices' => app_path('Modules/Notices'),
            'content_slots' => app_path('Modules/ContentSlots'),
            'pages' => app_path('Modules/Pages'),
            'news' => app_path('Modules/News'),
            'articles' => app_path('Modules/Articles'),
            // Venues now belongs to the Events package in the shared Core.
            'venues' => base_path('vendor/maracuja-digital/site-core-events'),
            'events' => base_path('vendor/maracuja-digital/site-core-events'),
            'gallery' => app_path('Modules/Gallery'),
            'media' => base_path('vendor/maracuja-digital/site-core-media'),
            'contact_form' => base_path('vendor/maracuja-digital/site-core-contact'),
            'assistant' => base_path('vendor/maracuja-digital/site-core-assistant'),
            'audience' => base_path('vendor/maracuja-digital/site-core-audience'),
            'brevo' => base_path('vendor/maracuja-digital/site-core-brevo'),
            'campaigns' => app_path('Modules/Campaigns'),
        ];

        return is_dir($paths[$module] ?? app_path('Modules/'.str($module)->studly()));
    }

    public static function offer(): string
    {
        return (string) config('maracuja.offer', 'signature');
    }

    public static function developerToolEnabled(string $tool): bool
    {
        return (bool) config("maracuja.developer_tools.{$tool}", false);
    }
}
