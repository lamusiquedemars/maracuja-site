<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabasePrefixConfigurationTest extends TestCase
{
    #[Test]
    public function mysql_and_mariadb_use_the_configured_table_prefix(): void
    {
        config([
            'database.connections.mysql.prefix' => 'starter_',
            'database.connections.mariadb.prefix' => 'starter_',
        ]);

        $this->assertSame('starter_', config('database.connections.mysql.prefix'));
        $this->assertSame('starter_', config('database.connections.mariadb.prefix'));
        $this->assertTrue(config('database.connections.mysql.prefix_indexes'));
        $this->assertTrue(config('database.connections.mariadb.prefix_indexes'));
    }
}
