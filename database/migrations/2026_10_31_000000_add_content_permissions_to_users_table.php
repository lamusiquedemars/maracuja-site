<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('content_permissions')->nullable();
        });

        // Preserve the privilege formerly granted by the hard-coded address.
        // It becomes an ordinary explicit role, so subsequent access decisions
        // no longer depend on an email address.
        DB::table('users')->whereRaw('LOWER(email) = ?', ['ivo@maracujadigital.fr'])
            ->update(['role' => 'admin', 'is_admin' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('content_permissions');
        });
    }
};
