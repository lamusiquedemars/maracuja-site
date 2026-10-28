<?php

namespace App\Support;

use JsonException;

final class SitePolicyReport
{
    /** @return array{mode: string, path: string, errors: list<string>, rows: list<array{capability: string, policy: bool, installed: bool, runtime: bool, status: string}>} */
    public function inspect(): array
    {
        $path = (string) config('maracuja.control.policy_manifest_path');
        $report = [
            'mode' => (string) config('maracuja.control.policy_mode', 'observe'),
            'path' => $path,
            'errors' => [],
            'rows' => [],
        ];

        if ($path === '' || ! is_file($path)) {
            $report['errors'][] = 'Manifeste de politique introuvable.';

            return $report;
        }

        try {
            $manifest = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $report['errors'][] = 'Manifeste de politique JSON invalide.';

            return $report;
        }

        if (($manifest['schema_version'] ?? null) !== 1 || ! is_array($manifest['modules'] ?? null)) {
            $report['errors'][] = 'Manifeste de politique incompatible.';

            return $report;
        }

        foreach ($manifest['modules'] as $capability => $definition) {
            if (! is_string($capability) || ! is_array($definition) || ! is_bool($definition['enabled'] ?? null)) {
                $report['errors'][] = 'Définition de capacité invalide.';

                continue;
            }

            $policyEnabled = $definition['enabled'];
            $installed = Modules::installed($capability);
            $runtimeEnabled = Modules::enabled($capability);
            $status = $policyEnabled === $runtimeEnabled && (! $policyEnabled || $installed)
                ? 'ok'
                : 'drift';

            $report['rows'][] = [
                'capability' => $capability,
                'policy' => $policyEnabled,
                'installed' => $installed,
                'runtime' => $runtimeEnabled,
                'status' => $status,
            ];
        }

        return $report;
    }
}
