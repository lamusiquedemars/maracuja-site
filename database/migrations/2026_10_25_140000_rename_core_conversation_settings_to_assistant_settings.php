<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * This starter previously installed the early Core package under the
     * misleading `conversations` name. Its table must not be confused with
     * the independent Conversations domain used by Cremona or older sites.
     */
    public function up(): void
    {
        if (Schema::hasTable('conversation_settings') && ! Schema::hasTable('assistant_settings')) {
            Schema::rename('conversation_settings', 'assistant_settings');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assistant_settings') && ! Schema::hasTable('conversation_settings')) {
            Schema::rename('assistant_settings', 'conversation_settings');
        }
    }
};
