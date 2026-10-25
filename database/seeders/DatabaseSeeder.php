<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * The reference application is Maracuja Digital. Client sites get their
     * own project-specific seeders rather than inheriting generic placeholder
     * content from this repository.
     */
    public function run(): void
    {
        $this->call(MaracujaDigitalDemoSeeder::class);
    }
}
