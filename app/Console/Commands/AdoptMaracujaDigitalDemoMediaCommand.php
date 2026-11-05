<?php

namespace App\Console\Commands;

use App\Modules\Media\Models\MediaAsset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AdoptMaracujaDigitalDemoMediaCommand extends Command
{
    protected $signature = 'maracuja:demo:adopt-media {--apply : Remplace les chemins démo par leurs médias centralisés}';

    protected $description = 'Migre les dernières références Maracuja Digital du dossier public de démonstration vers la médiathèque centrale.';

    private const PREFIX = '/demo/maracuja-digital/';

    /** @var array<string, string> */
    private const TABLES = [
        'gallery_images' => 'media_asset_id',
        'events' => 'image_media_id',
    ];

    public function handle(): int
    {
        $entries = collect(self::TABLES)
            ->flatMap(function (string $mediaColumn, string $table): array {
                if (! Schema::hasTable($table)) {
                    return [];
                }

                return DB::table($table)
                    ->where('image_path', 'like', self::PREFIX.'%')
                    ->orderBy('id')
                    ->get(['id', 'image_path', $mediaColumn])
                    ->map(fn (object $row): array => [
                        'table' => $table,
                        'id' => (int) $row->id,
                        'image_path' => (string) $row->image_path,
                        'media_column' => $mediaColumn,
                        'media_asset_id' => $row->{$mediaColumn},
                    ])->all();
            });

        $this->table(['Référence', 'Chemin historique', 'Média cible'], $entries
            ->map(function (array $entry): array {
                $media = $this->mediaFor($entry);

                return [
                    $entry['table'].'#'.$entry['id'],
                    $entry['image_path'],
                    $media ? '#'.$media->getKey().' — '.$media->original_name : 'Introuvable',
                ];
            })->all());

        if (! $this->option('apply')) {
            $this->warn('Prévisualisation uniquement : aucune référence n’est modifiée.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($entries): void {
            foreach ($entries as $entry) {
                $media = $this->mediaFor($entry);

                if ($media === null) {
                    throw new RuntimeException("Média central introuvable pour {$entry['image_path']}.");
                }

                DB::table($entry['table'])
                    ->where('id', $entry['id'])
                    ->update([
                        $entry['media_column'] => $media->getKey(),
                        'image_path' => null,
                        'updated_at' => now(),
                    ]);
            }
        });

        $this->info('Références migrées : le dossier public de démonstration n’est plus utilisé par les contenus.');

        return self::SUCCESS;
    }

    /** @param array{image_path: string, media_asset_id: mixed} $entry */
    private function mediaFor(array $entry): ?MediaAsset
    {
        if ($entry['media_asset_id'] !== null) {
            return MediaAsset::query()->find($entry['media_asset_id']);
        }

        return MediaAsset::query()->where('original_name', basename($entry['image_path']))->first();
    }
}
