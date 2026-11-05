<?php

namespace App\Console\Commands;

use App\Modules\Articles\Models\Article;
use App\Modules\Media\Models\MediaAsset;
use App\Modules\Media\Services\MediaStorageService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;

class RestoreMaracujaDigitalMediaCommand extends Command
{
    protected $signature = 'maracuja:demo:restore-media {--apply : Importe les visuels et rattache les articles non encore illustrés}';

    protected $description = 'Restaure les visuels de référence Maracuja Digital dans la médiathèque sans écraser les médias existants.';

    /** @var array<string, array{0: string, 1: string, 2: string}> */
    private const MEDIA = [
        'ville-a-velo.png' => ['Ville à vélo', 'Illustration pastel d’une rue de ville avec un vélo au premier plan.', 'Illustration de démonstration Maracuja Digital.'],
        'plantes-et-fleur.png' => ['Plantes et fleur', 'Illustration pastel de plantes vertes et d’une fleur dans un pot.', 'Illustration de démonstration Maracuja Digital.'],
        'petit-dejeuner.png' => ['Petit déjeuner', 'Illustration pastel d’un café, d’un croissant, de fruits et de myrtilles.', 'Illustration de démonstration Maracuja Digital.'],
        'route-au-soleil.png' => ['Route au soleil', 'Illustration pastel d’une voiture électrique sur une route de campagne au soleil.', 'Illustration de démonstration Maracuja Digital.'],
        'coin-lecture.png' => ['Coin lecture', 'Illustration pastel d’un fauteuil, d’une lampe, de livres et d’une plante.', 'Illustration de démonstration Maracuja Digital.'],
        'plage-au-coucher-du-soleil.png' => ['Plage au coucher du soleil', 'Illustration pastel d’un transat et d’un parasol face à la mer au soleil couchant.', 'Illustration de démonstration Maracuja Digital.'],
        'paysage.png' => ['Paysage — préparer un site', 'Paysage illustré avec un chemin qui traverse des collines au lever du soleil.', 'Un projet commence par une direction claire.'],
        'atelier-numerique.png' => ['Atelier numérique', 'Ordinateur portable, carnet, plante et verre posés sur un bureau lumineux.', 'Un atelier numérique à taille humaine.'],
        'exploration.png' => ['Exploration', 'Illustration d’une planète pastel, de satellites et d’étoiles dans l’espace.', 'Illustration de démonstration Maracuja Digital.'],
        'concert-en-plein-air.png' => ['Concert en plein air', 'Violoniste et pianiste sur une scène extérieure devant un public au coucher du soleil.', 'Illustration de démonstration Maracuja Digital.'],
    ];

    /** @var array<string, string> */
    private const ARTICLE_IMAGES = [
        'preparer-un-site-qui-vous-ressemble' => 'paysage.png',
        'faire-vivre-ses-realisations' => 'atelier-numerique.png',
    ];

    public function handle(MediaStorageService $storage): int
    {
        $this->table(['Visuel', 'État'], collect(self::MEDIA)
            ->map(fn (array $metadata, string $filename): array => [
                $filename,
                MediaAsset::query()->where('original_name', $filename)->exists() ? 'Déjà catalogué' : 'À importer',
            ])->all());

        if (! $this->option('apply')) {
            $this->warn('Prévisualisation uniquement : aucune image ni donnée n’est modifiée. Relancer avec --apply après sauvegarde validée.');

            return self::SUCCESS;
        }

        $media = [];

        foreach (self::MEDIA as $filename => [$displayName, $altText, $caption]) {
            $media[$filename] = MediaAsset::query()->where('original_name', $filename)->first();

            if ($media[$filename] !== null) {
                continue;
            }

            $source = public_path('demo/maracuja-digital/'.$filename);

            if (! is_file($source)) {
                $this->error("Visuel source introuvable : {$filename}");

                return self::FAILURE;
            }

            $media[$filename] = $storage->store(new UploadedFile(
                $source,
                $filename,
                mime_content_type($source) ?: 'image/png',
                null,
                true,
            ), [
                'display_name' => $displayName,
                'alt_text' => $altText,
                'caption' => $caption,
                'credit' => 'Maracuja Digital',
            ]);
        }

        foreach (self::ARTICLE_IMAGES as $slug => $filename) {
            $article = Article::query()->where('slug', $slug)->first();

            if ($article === null) {
                $this->warn("Article introuvable : {$slug}");

                continue;
            }

            if ($article->image_media_id !== null) {
                $this->line("Article conservé (image déjà définie) : {$article->title}");

                continue;
            }

            $article->update([
                'image_media_id' => $media[$filename]->getKey(),
                'image_path' => null,
            ]);

            $this->info("Image rattachée : {$article->title}");
        }

        $this->info('Médiathèque restaurée : les sources de démonstration sont conservées et aucun média existant n’a été remplacé.');

        return self::SUCCESS;
    }
}
