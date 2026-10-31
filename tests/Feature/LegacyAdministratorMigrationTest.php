<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class LegacyAdministratorMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_former_address_privilege_is_preserved_as_an_explicit_role(): void
    {
        $migration = require database_path('migrations/2026_10_31_000000_add_content_permissions_to_users_table.php');
        $legacy = User::factory()->create(['email' => 'ivo@maracujadigital.fr', 'role' => User::ROLE_VIEWER, 'is_admin' => false]);
        $other = User::factory()->create(['role' => User::ROLE_VIEWER, 'is_admin' => false]);

        // The test database already has the column; exercise the data part
        // without running MySQL DDL inside the test transaction.
        Schema::shouldReceive('table')->once()->with('users', Mockery::type('Closure'));
        $migration->up();

        $this->assertSame(User::ROLE_ADMIN, $legacy->fresh()->role);
        $this->assertTrue($legacy->fresh()->is_admin);
        $this->assertFalse($other->fresh()->is_admin);
    }
}
