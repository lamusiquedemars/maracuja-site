<?php

namespace App\Support;

use App\Models\User;
use Maracuja\SiteCore\Foundation\Contracts\FoundationAccessGate;

final class CoreFoundationAccessGate implements FoundationAccessGate
{
    public function allows(string $ability): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isAdministrator()) {
            return true;
        }

        $permission = match ($ability) {
            'events.manage' => 'events',
            'events.venues.manage' => 'venues',
            'events.participants.manage' => 'participants',
            'contact.requests.manage' => 'inquiries',
            'gallery.manage' => 'gallery',
            'media.manage' => 'media',
            'foundation.pages.manage' => 'pages',
            'foundation.content.manage' => 'content_slots',
            'foundation.notices.manage' => 'notices',
            default => null,
        };

        return $permission !== null && $user->canEditContent() && $user->canAccessContent($permission);
    }
}
