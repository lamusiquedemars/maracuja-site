<?php

namespace App\Support;

use App\Models\User;
use App\Modules\Articles\Models\Article;
use App\Modules\ContentSlots\Models\ContentSlot;
use App\Modules\Gallery\Models\Gallery;
use App\Modules\Media\Models\MediaAsset;
use App\Modules\News\Models\NewsPost;
use App\Modules\Notices\Models\SiteNotice;
use App\Modules\Pages\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Maracuja\SiteCore\Audience\Models\AudienceContact;
use Maracuja\SiteCore\Audience\Models\AudienceSegment;
use Maracuja\SiteCore\Audience\Models\SegmentMessage;
use Maracuja\SiteCore\Contact\Models\Inquiry;
use Maracuja\SiteCore\Events\Models\Event;
use Maracuja\SiteCore\Events\Models\Participant;
use Maracuja\SiteCore\Events\Models\Venue;

final class ContentPermissions
{
    public static function labels(): array
    {
        return [
            'pages' => 'Pages', 'notices' => 'Annonces courtes',
            'content_slots' => 'Contenus courts', 'news' => 'Actualités',
            'articles' => 'Articles', 'media' => 'Médias', 'gallery' => 'Galeries',
            'events' => 'Événements', 'venues' => 'Lieux', 'participants' => 'Intervenants',
            'inquiries' => 'Demandes reçues', 'audience' => 'Audience et campagnes',
        ];
    }

    public static function forModel(?string $model): ?string
    {
        return match ($model) {
            Page::class => 'pages', SiteNotice::class => 'notices', ContentSlot::class => 'content_slots',
            NewsPost::class => 'news', Article::class => 'articles', MediaAsset::class => 'media',
            Gallery::class => 'gallery', Event::class => 'events', Venue::class => 'venues',
            Participant::class => 'participants', Inquiry::class => 'inquiries',
            AudienceContact::class, AudienceSegment::class, SegmentMessage::class => 'audience',
            default => null,
        };
    }

    public static function labelsForForm(): array
    {
        $labels = self::labels();
        $modules = ['inquiries' => 'contact_form', 'participants' => 'events'];

        foreach ($labels as $key => $label) {
            // Older sites provide media permanently without a module flag.
            if ($key === 'media' && ! isset($modules[$key]) && config('maracuja.modules.media') === null) {
                continue;
            }

            if (! Modules::enabled($modules[$key] ?? $key)) {
                $labels[$key] = $label.' (module inactif)';
            }
        }

        return $labels;
    }

    public static function allows(User $user, ?string $model): bool
    {
        $permission = self::forModel($model);

        // These screens contain custom write actions that are not yet
        // available in a read-only form. Never expose them to a reader.
        if (! $user->isAdministrator() && $user->role === User::ROLE_VIEWER && in_array($permission, ['inquiries', 'audience', 'conversations'], true)) {
            return false;
        }

        if ($permission !== null) {
            return $user->canAccessContent($permission);
        }

        // Explicitly restricted accounts must not gain access to a new model
        // merely because it has not yet been mapped to a visible function.
        return $user->isAdministrator() || $user->content_permissions === null
            || ! $model || ! is_a($model, Model::class, true);
    }
}
