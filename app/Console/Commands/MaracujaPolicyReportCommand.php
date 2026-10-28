<?php

namespace App\Console\Commands;

use App\Support\SitePolicyReport;
use Illuminate\Console\Command;

class MaracujaPolicyReportCommand extends Command
{
    protected $signature = 'maracuja:policy-report {--strict : Échoue si le manifeste et le runtime divergent}';

    protected $description = 'Compare le manifeste de capacités Maracuja Control à l’état runtime local.';

    public function __construct(private readonly SitePolicyReport $report)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->report->inspect();

        $this->info("Politique Maracuja Control ({$result['mode']})");
        $this->line("Manifeste : {$result['path']}");

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        $this->table(
            ['Capacité', 'Politique', 'Installée', 'Runtime', 'État'],
            array_map(fn (array $row): array => [
                $row['capability'],
                $row['policy'] ? 'oui' : 'non',
                $row['installed'] ? 'oui' : 'non',
                $row['runtime'] ? 'oui' : 'non',
                strtoupper($row['status']),
            ], $result['rows']),
        );

        $hasDrift = $result['errors'] !== []
            || collect($result['rows'])->contains(fn (array $row): bool => $row['status'] === 'drift');

        if ($hasDrift) {
            $this->warn('Écart détecté : le mode observation ne modifie pas le runtime local.');

            return $this->option('strict') ? self::FAILURE : self::SUCCESS;
        }

        $this->info('Politique et runtime concordent.');

        return self::SUCCESS;
    }
}
