<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class MaracujaPolicyReportCommandTest extends TestCase
{
    public function test_policy_report_is_observational_when_runtime_differs(): void
    {
        config([
            'maracuja.control.policy_mode' => 'observe',
            'maracuja.control.policy_manifest_path' => base_path('tests/Fixtures/policy-drift.json'),
            'maracuja.modules.brevo' => false,
        ]);

        $this->artisan('maracuja:policy-report')
            ->expectsOutputToContain('Écart détecté')
            ->assertExitCode(0);
    }

    public function test_strict_policy_report_fails_when_runtime_differs(): void
    {
        config([
            'maracuja.control.policy_manifest_path' => base_path('tests/Fixtures/policy-drift.json'),
            'maracuja.modules.brevo' => false,
        ]);

        $this->artisan('maracuja:policy-report --strict')
            ->expectsOutputToContain('Écart détecté')
            ->assertExitCode(1);
    }
}
