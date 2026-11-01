<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ImportMaracujaContent extends Command
{
    protected $signature = 'maracuja:import-content {file : Chemin absolu du fichier JSON exporté}';

    protected $description = 'Importe les contenus éditoriaux Maracuja sans toucher aux comptes ni aux tables techniques.';

    /** @var list<string> */
    private array $tables = [
        'media_assets',
        'site_settings',
        'content_slots',
        'pages',
        'news_posts',
        'articles',
        'venues',
        'events',
        'galleries',
        'gallery_images',
        'site_notices',
    ];

    public function handle(): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->error("Fichier introuvable : {$file}");

            return self::FAILURE;
        }

        $payload = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        $datasets = $payload['tables'] ?? null;

        if (! is_array($datasets)) {
            throw new RuntimeException('Le fichier ne contient pas de jeux de données valides.');
        }

        DB::transaction(function () use ($datasets): void {
            foreach ($this->tables as $table) {
                $rows = $datasets[$table] ?? [];

                if (! is_array($rows) || $rows === []) {
                    continue;
                }

                if (! Schema::hasTable($table)) {
                    throw new RuntimeException("Table distante introuvable : {$table}");
                }

                $columns = array_keys($rows[0]);

                if (! in_array('id', $columns, true)) {
                    throw new RuntimeException("La table {$table} ne contient pas de clé id.");
                }

                DB::table($table)->upsert($rows, ['id'], array_values(array_diff($columns, ['id'])));
                $this->line("{$table} : ".count($rows).' contenu(s) importé(s).');
            }
        });

        $this->info('Import des contenus terminé.');

        return self::SUCCESS;
    }
}
