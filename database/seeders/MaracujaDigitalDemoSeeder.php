<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Articles\Models\Article;
use App\Modules\ContentSlots\Models\ContentSlot;
use App\Modules\Gallery\Models\Gallery;
use App\Modules\Gallery\Models\GalleryImage;
use App\Modules\Media\Models\MediaAsset;
use App\Modules\Media\Services\MediaStorageService;
use App\Modules\News\Models\NewsPost;
use App\Modules\Notices\Models\SiteNotice;
use App\Modules\Pages\Models\Page;
use App\Modules\SiteSettings\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Maracuja\SiteCore\Events\Models\Event;

/** Public reference content for site.maracujadigital.fr. */
class MaracujaDigitalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $this->removeLegacyStarterContent();

        SiteSetting::query()->updateOrCreate(['id' => 1], [
            'site_name' => 'Maracuja Digital',
            'baseline' => 'Sites clairs, administrables et durables.',
            'default_seo_title' => 'Maracuja Digital — sites web utiles et durables',
            'default_seo_description' => 'Maracuja Digital conçoit des sites vitrines et administrables, adaptés aux besoins réels des indépendants et petites structures.',
            'contact_email' => 'contact@maracujadigital.fr',
            'phone' => '+33 7 86 43 15 83',
            'address' => 'Collonges-au-Mont-d’Or, France',
            'social_links' => [],
            'contact_form_show_name' => true,
            'contact_form_show_phone' => true,
            'contact_form_show_subject' => true,
            'contact_form_send_admin_email' => true,
            'contact_form_send_confirmation_email' => false,
        ]);

        collect([
            $this->slot('home.hero.cta_label', 'Accueil', 'Parler d’un projet', 'Libellé du bouton principal.'),
            $this->slot('home.hero.secondary_cta_label', 'Accueil', 'Voir les formules', 'Libellé du bouton secondaire.'),
            $this->slot('home.intro.title', 'Accueil', 'Un site à la bonne mesure', 'Titre de la présentation des formules.'),
            $this->slot('home.intro.text', 'Accueil', 'Maracuja Digital conçoit des sites pour présenter une activité, recevoir des demandes et publier ce qui compte — sans transformer votre quotidien en gestion de logiciel.', 'Introduction des formules.'),
            $this->slot('home.offer.essence.text', 'Accueil', 'Une présence nette pour présenter une activité, inspirer confiance et recevoir les premières demandes.', 'Texte de la formule Essence.'),
            $this->slot('home.offer.signature.text', 'Accueil', 'Un site administrable pour faire vivre des réalisations, des actualités et des contenus utiles.', 'Texte de la formule Signature.'),
            $this->slot('home.offer.univers.text', 'Accueil', 'Une solution sur mesure lorsque l’activité demande un catalogue, un espace métier ou une connexion à un outil existant.', 'Texte de la formule Univers.'),
            $this->slot('home.admin.title', 'Accueil', 'Une administration qui respecte le métier', 'Titre de la section administration.'),
            $this->slot('home.admin.intro', 'Accueil', 'Chaque projet montre seulement les outils nécessaires. Les contenus restent simples à retrouver, à relire et à publier.', 'Introduction de la section administration.'),
            $this->slot('home.admin.quote', 'Accueil', 'Un bon outil s’efface derrière le travail de celles et ceux qui l’utilisent.', 'Citation de principe.'),
            $this->slot('home.admin.modules.text', 'Accueil', 'Pages, réalisations, actualités, médias et demandes de contact sont activés selon le projet — jamais par défaut.', 'Texte de la carte modules.'),
            $this->slot('home.admin.pages.text', 'Accueil', 'La structure reste solide ; les équipes modifient les textes et les visuels prévus, sans risquer de casser le site.', 'Texte de la carte pages.'),
            $this->slot('gallery.title', 'Réalisations', 'Trois manières de rendre un site utile', 'Titre de la galerie de référence.'),
            $this->slot('gallery.intro', 'Réalisations', 'Une même exigence : une présence claire, un contenu facile à tenir et une identité qui appartient vraiment au projet.', 'Introduction de la galerie de référence.'),
            $this->slot('home.news.title', 'Accueil', 'Ce qui fait avancer les projets', 'Titre de la sélection éditoriale.'),
            $this->slot('home.news.intro', 'Accueil', 'Quelques repères sur la manière de cadrer, construire et faire vivre un site.', 'Introduction de la sélection éditoriale.'),
            $this->slot('home.cta.title', 'Accueil', 'Parlons de ce dont votre site a réellement besoin', 'Titre du CTA final.'),
            $this->slot('home.cta.text', 'Accueil', 'Un premier échange permet de choisir le bon niveau de projet, sans suréquiper votre activité.', 'Texte du CTA final.'),
            $this->slot('home.cta.label', 'Accueil', 'Nous contacter', 'Libellé du CTA final.'),
            $this->slot('articles.public_label', 'Publications', 'Repères', 'Libellé public des articles.'),
            $this->slot('articles.index.subtitle', 'Publications', 'Des notes pour choisir, préparer et faire vivre un site avec justesse.', 'Introduction de la page publications.'),
            $this->slot('events.public_label', 'Agenda', 'Agenda', 'Libellé prêt pour les futurs événements réels.'),
            $this->slot('events.index.subtitle', 'Agenda', 'Les rendez-vous publics seront publiés ici lorsqu’ils existeront.', 'Introduction prête pour les futurs événements réels.'),
            $this->slot('services.hero.cta_label', 'Formules', 'Parler du projet', 'Libellé du CTA Services.'),
            $this->slot('services.offers.title', 'Formules', 'Trois niveaux, un même soin', 'Titre de la présentation des formules.'),
            $this->slot('services.offers.intro', 'Formules', 'Le point de départ est toujours le besoin réel : présenter, publier, administrer ou connecter un métier.', 'Introduction des formules.'),
            $this->slot('services.essence.price', 'Formules', 'Pour aller à l’essentiel', 'Accroche Essence.'),
            $this->slot('services.essence.description', 'Formules', 'Une présence professionnelle, structurée et rapide à prendre en main pour les activités qui ont surtout besoin d’être bien comprises.', 'Description Essence.'),
            $this->slot('services.signature.price', 'Formules', 'Pour faire vivre le site', 'Accroche Signature.'),
            $this->slot('services.signature.description', 'Formules', 'Des contenus administrables, une galerie, des publications et les parcours de contact qui accompagnent une activité dans le temps.', 'Description Signature.'),
            $this->slot('services.univers.price', 'Formules', 'Pour un besoin spécifique', 'Accroche Univers.'),
            $this->slot('services.univers.description', 'Formules', 'Un module métier ou une intégration précise, conçu autour du fonctionnement existant plutôt qu’autour d’une solution standard.', 'Description Univers.'),
            $this->slot('services.common.title', 'Formules', 'Ce qui reste commun', 'Titre du socle partagé.'),
            $this->slot('services.common.tech.text', 'Formules', 'Un socle maintenable, des mises à jour maîtrisées et une livraison qui ne dépend pas d’une collection de plugins.', 'Texte du socle technique.'),
            $this->slot('services.common.front.text', 'Formules', 'Une direction claire, des composants sobres et une expérience qui reste rapide sur tous les écrans.', 'Texte du socle front.'),
            $this->slot('services.cta.title', 'Formules', 'Choisissons le bon point de départ', 'Titre du CTA Services.'),
            $this->slot('services.cta.text', 'Formules', 'Le périmètre se construit autour de votre activité, de vos contenus et de ce qui doit réellement évoluer.', 'Texte du CTA Services.'),
            $this->slot('services.cta.label', 'Formules', 'Présenter le besoin', 'Libellé du CTA Services.'),
        ])->each(fn (array $slot) => ContentSlot::query()->updateOrCreate(['key' => $slot['key']], $slot + ['is_locked' => true]));

        $this->seedPages($now);
        $this->seedNews($now);
        $this->seedArticles($now);
        $this->seedGallery();
        $this->seedEvent($now);
    }

    private function seedPages($now): void
    {
        collect([
            ['slug' => 'accueil', 'title' => 'Accueil', 'template' => 'landing', 'type' => Page::TYPE_SYSTEM, 'excerpt' => 'Sites clairs, administrables et durables.', 'hero_title' => 'Des sites qui suivent le rythme de votre activité', 'hero_subtitle' => 'Maracuja Digital conçoit des sites vitrines et administrables pour les indépendants, ateliers et petites structures qui veulent une présence juste et durable.', 'content' => null, 'seo_title' => 'Maracuja Digital — sites web utiles et durables', 'seo_description' => 'Des sites vitrines et administrables conçus autour des besoins réels de votre activité.'],
            ['slug' => 'services', 'title' => 'Formules', 'template' => 'services', 'type' => Page::TYPE_SYSTEM, 'excerpt' => 'Des formats de projet à adapter à l’activité.', 'hero_title' => 'Un site à la bonne mesure', 'hero_subtitle' => 'Présenter, publier, administrer ou connecter un métier : le niveau de solution se choisit à partir de ce qui doit vraiment avancer.', 'content' => null, 'seo_title' => 'Formules — Maracuja Digital', 'seo_description' => 'Essence, Signature et Univers : trois niveaux de projet à adapter aux besoins réels.'],
            ['slug' => 'contact', 'title' => 'Contact', 'template' => 'contact', 'type' => Page::TYPE_SYSTEM, 'excerpt' => 'Parlons de votre activité et de votre site.', 'hero_title' => 'Parlons de votre projet', 'hero_subtitle' => 'Quelques lignes suffisent pour commencer : votre activité, ce que vous voulez rendre plus clair et ce qui doit évoluer.', 'content' => null, 'seo_title' => 'Contact — Maracuja Digital', 'seo_description' => 'Présentez votre projet à Maracuja Digital.'],
            ['slug' => 'actualites', 'title' => 'Actualités', 'template' => 'module', 'type' => Page::TYPE_MODULE, 'excerpt' => 'Les nouvelles et repères publiés par Maracuja Digital.', 'hero_title' => 'Actualités', 'hero_subtitle' => 'Des nouvelles du studio et des repères pour les projets en cours.', 'content' => null, 'seo_title' => 'Actualités — Maracuja Digital', 'seo_description' => 'Les actualités de Maracuja Digital.'],
            ['slug' => 'mentions-legales', 'title' => 'Mentions légales', 'template' => 'default', 'type' => Page::TYPE_TEXT, 'excerpt' => 'Informations légales.', 'hero_title' => 'Mentions légales', 'hero_subtitle' => null, 'content' => '<p><strong>Éditeur :</strong> Maracuja Digital, studio digital indépendant.</p><p><strong>Contact :</strong> <a href="mailto:contact@maracujadigital.fr">contact@maracujadigital.fr</a></p><p><strong>Site :</strong> <a href="https://maracujadigital.fr">maracujadigital.fr</a></p>', 'seo_title' => 'Mentions légales — Maracuja Digital', 'seo_description' => 'Mentions légales de Maracuja Digital.'],
        ])->each(fn (array $page) => Page::query()->updateOrCreate(['slug' => $page['slug']], $page + ['is_published' => true, 'published_at' => $now]));
    }

    private function seedNews($now): void
    {
        collect([
            ['slug' => 'site-pilote-en-ligne', 'title' => 'Le site pilote est en ligne', 'excerpt' => 'Une démonstration publique de Maracuja Site : contenus administrables, médias, contact et architecture de livraison maîtrisée.', 'content' => '<p>Le site pilote permet de voir un projet Maracuja Site dans son environnement réel. Il sert à éprouver les contenus, l’administration et la mise en ligne avant les projets clients.</p>', 'is_pinned' => true, 'has_detail_page' => true, 'published_at' => $now->copy()->subDays(2)],
            ['slug' => 'commencer-par-le-besoin', 'title' => 'Commencer par le besoin, pas par la liste des fonctionnalités', 'excerpt' => 'Un site n’a pas besoin d’accumuler les modules pour devenir utile : il doit répondre clairement à la manière dont l’activité fonctionne.', 'content' => '<p>Chaque projet commence par les contenus à rendre visibles, les personnes qui les administrent et les décisions que le site doit faciliter.</p>', 'is_pinned' => false, 'has_detail_page' => true, 'published_at' => $now->copy()->subWeeks(2)],
            ['slug' => 'contenus-faciles-a-faire-vivre', 'title' => 'Des contenus faciles à faire vivre', 'excerpt' => 'La qualité d’un site se joue aussi après sa mise en ligne : publier une information ou une réalisation doit rester simple.', 'content' => '<p>Les interfaces de gestion sont limitées à ce qui sert vraiment. Le projet reste lisible pour celles et ceux qui l’animent.</p>', 'is_pinned' => false, 'has_detail_page' => true, 'published_at' => $now->copy()->subMonth()],
        ])->each(fn (array $post) => NewsPost::query()->updateOrCreate(['slug' => $post['slug']], $post + ['is_published' => true, 'expires_at' => null]));
    }

    private function seedArticles($now): void
    {
        collect([
            ['slug' => 'preparer-un-site-qui-vous-ressemble', 'title' => 'Préparer un site qui vous ressemble', 'excerpt' => 'Trois questions utiles avant de choisir une formule ou de dessiner une première page.', 'seo_title' => 'Préparer un site qui vous ressemble — Maracuja Digital', 'seo_description' => 'Trois questions utiles pour préparer un projet de site.', 'image_media_id' => $this->media('paysage.png', 'Paysage — préparer un site', 'Paysage illustré avec un chemin qui traverse des collines au lever du soleil.', 'Un projet commence par une direction claire.' )->id, 'image_path' => null, 'published_at' => $now->copy()->subWeeks(3), 'body_blocks' => [['type' => 'rich_text', 'text' => '<p>Avant les maquettes, il est utile de savoir ce que les visiteurs doivent comprendre en premier, quels contenus vont évoluer et qui les fera vivre.</p>'], ['type' => 'rich_text', 'text' => '<p>Ces réponses permettent de choisir une structure claire et une administration proportionnée.</p>']]],
            ['slug' => 'faire-vivre-ses-realisations', 'title' => 'Faire vivre ses réalisations sans refaire son site', 'excerpt' => 'Une galerie utile doit pouvoir être mise à jour au fil des projets, sans transformer chaque ajout en opération technique.', 'seo_title' => 'Faire vivre ses réalisations — Maracuja Digital', 'seo_description' => 'Comment garder une galerie de réalisations vivante et simple à administrer.', 'image_media_id' => $this->media('atelier-numerique.png', 'Atelier numérique', 'Ordinateur portable, carnet, plante et verre posés sur un bureau lumineux.', 'Un atelier numérique à taille humaine.' )->id, 'image_path' => null, 'published_at' => $now->copy()->subWeeks(6), 'body_blocks' => [['type' => 'rich_text', 'text' => '<p>Une réalisation a besoin d’un visuel, d’un contexte et d’une place cohérente dans le récit de l’activité. Le reste doit rester simple.</p>']]],
        ])->each(fn (array $article) => Article::query()->updateOrCreate(['slug' => $article['slug']], $article + ['is_published' => true]));
    }

    private function seedGallery(): void
    {
        $gallery = Gallery::query()->updateOrCreate(['slug' => 'home'], [
            'title' => 'Trois manières de rendre un site utile',
            'intro' => 'Une même exigence : une présence claire, un contenu facile à tenir et une identité qui appartient vraiment au projet.',
            'position' => 1,
            'is_published' => true,
        ]);

        collect([
            ['file' => 'paysage.png', 'title' => 'Un projet qui trouve son rythme', 'caption' => 'Un site se construit pas à pas : clarifier, choisir et avancer avec ce qui compte vraiment.', 'alt_text' => 'Paysage illustré avec un chemin qui traverse des collines au lever du soleil.', 'position' => 1],
            ['file' => 'atelier-numerique.png', 'title' => 'Un atelier numérique à taille humaine', 'caption' => 'Le numérique reste un outil de travail : précis, accessible et au service d’une activité réelle.', 'alt_text' => 'Ordinateur portable, carnet, plante et verre posés sur un bureau lumineux.', 'position' => 2],
            ['file' => 'exploration.png', 'title' => 'Une idée qui prend de la hauteur', 'caption' => 'La bonne direction donne de l’espace au projet sans lui ajouter de complexité inutile.', 'alt_text' => 'Illustration d’une planète pastel, de satellites et d’étoiles dans l’espace.', 'position' => 3],
            ['file' => 'concert-en-plein-air.png', 'title' => 'Le concert en plein air', 'caption' => 'Une page événementielle peut présenter un rendez-vous, son contexte et les informations pratiques.', 'alt_text' => 'Violoniste et pianiste sur une scène extérieure devant un public au coucher du soleil.', 'position' => 4],
        ])->each(function (array $image) use ($gallery): void {
            $media = $this->media(
                $image['file'],
                $image['title'],
                $image['alt_text'],
                $image['caption'],
            );

            GalleryImage::query()->updateOrCreate(
                ['gallery_id' => $gallery->id, 'title' => $image['title']],
                [
                    'caption' => $image['caption'],
                    'credit' => 'Maracuja Digital',
                    'alt_text' => $image['alt_text'],
                    'media_asset_id' => $media->id,
                    // Kept as a compatibility fallback for the legacy non-nullable column.
                    // The public renderer prefers media_asset_id.
                    'image_path' => '/demo/maracuja-digital/'.$image['file'],
                    'width' => $media->width,
                    'height' => $media->height,
                    'position' => $image['position'],
                    'is_published' => true,
                ],
            );
        });
    }

    private function seedEvent($now): void
    {
        Event::query()->updateOrCreate(['slug' => 'concert-en-plein-air'], [
            'title' => 'Concert en plein air',
            'type' => 'Concert',
            'status' => Event::STATUS_SCHEDULED,
            'starts_at' => $now->copy()->addMonths(2)->startOfDay()->addHours(18),
            'ends_at' => $now->copy()->addMonths(2)->startOfDay()->addHours(19)->addMinutes(30),
            'timezone' => 'Europe/Paris',
            'excerpt' => 'Un exemple complet de rendez-vous public : date, détail, image et informations utiles.',
            'description' => '<p>Ce concert en plein air illustre la manière dont un projet culturel peut présenter un rendez-vous et garder son agenda à jour.</p>',
            'image_media_id' => $this->media('concert-en-plein-air.png', 'Concert en plein air', 'Violoniste et pianiste sur une scène extérieure devant un public au coucher du soleil.', 'Concert en plein air.')->id,
            'image_path' => null,
            'ticket_url' => null,
            'external_url' => null,
            'seo_title' => 'Concert en plein air — Maracuja Digital',
            'seo_description' => 'Exemple de page événementielle proposée par Maracuja Digital.',
            'is_featured' => true,
            'is_published' => true,
            'published_at' => $now,
        ]);
    }

    private function removeLegacyStarterContent(): void
    {
        // The starter account shipped with the old CMS demo must not survive a reference-site reset.
        User::query()->where('email', 'admin@maracuja.test')->delete();
        NewsPost::query()->whereIn('slug', ['maracuja-cms-v1', 'front-system', 'admin-simplifiee'])->delete();
        Page::query()->where('slug', 'methode')->delete();
        SiteNotice::query()->where('title', 'Annonce démo')->delete();

        if ($gallery = Gallery::query()->where('slug', 'home')->first()) {
            GalleryImage::query()->where('gallery_id', $gallery->id)->whereIn('title', ['Admin simple', 'Composants front', 'Thèmes clients'])->delete();
        }
    }

    private function media(string $filename, string $displayName, string $altText, string $caption): MediaAsset
    {
        if ($media = MediaAsset::query()->where('original_name', $filename)->first()) {
            return $media;
        }

        $path = public_path('demo/maracuja-digital/'.$filename);

        if (! is_file($path)) {
            throw new \RuntimeException("Le visuel de démonstration {$filename} est introuvable.");
        }

        return app(MediaStorageService::class)->store(new UploadedFile(
            $path,
            $filename,
            mime_content_type($path) ?: 'image/png',
            null,
            true,
        ), [
            'display_name' => $displayName,
            'alt_text' => $altText,
            'caption' => $caption,
            'credit' => 'Maracuja Digital',
        ]);
    }

    /** @return array{key: string, label: string, group: string, type: string, value: string, help_text: string} */
    private function slot(string $key, string $group, string $value, string $helpText): array
    {
        return ['key' => $key, 'label' => str($key)->replace(['.', '_'], ' ')->headline()->toString(), 'group' => $group, 'type' => 'textarea', 'value' => $value, 'help_text' => $helpText];
    }
}
