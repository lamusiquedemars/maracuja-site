<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $historicalPivot = Schema::hasTable('event_participants')
            && ! Schema::hasColumn('event_participants', 'name');

        // Historical installs used `event_participants` as the pivot and
        // `participants` for people. The shared module reverses this: its
        // reusable people live in `event_participants`, and its pivot is
        // singular (`event_participant`). Preserve both datasets while moving
        // them to the Core schema.
        if ($historicalPivot) {
            Schema::rename('event_participants', 'legacy_event_participants');
        }

        if (! Schema::hasTable('event_participants')) {
            Schema::create('event_participants', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('kind')->default('person');
                $table->text('excerpt')->nullable();
                $table->string('website_url')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        } else {
            if (! Schema::hasColumn('event_participants', 'kind')) {
                Schema::table('event_participants', function (Blueprint $table): void {
                    $table->string('kind')->default('person');
                });
            }

            if (! Schema::hasColumn('event_participants', 'excerpt')) {
                Schema::table('event_participants', function (Blueprint $table): void {
                    $table->text('excerpt')->nullable();
                });
            }
        }

        if ($historicalPivot && Schema::hasTable('participants')) {
            DB::table('participants')->orderBy('id')->each(function (object $participant): void {
                DB::table('event_participants')->insert([
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'slug' => $participant->slug,
                    'kind' => $participant->type ?: 'person',
                    'excerpt' => $participant->bio,
                    'website_url' => $participant->website_url,
                    'is_active' => $participant->is_active,
                    'created_at' => $participant->created_at,
                    'updated_at' => $participant->updated_at,
                ]);
            });
        }

        if (! Schema::hasTable('event_participant')) {
            Schema::create('event_participant', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('event_id')->constrained()->cascadeOnDelete();
                $table->foreignId('participant_id')->constrained('event_participants')->cascadeOnDelete();
                $table->string('role')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['event_id', 'participant_id']);
            });
        }

        if ($historicalPivot) {
            DB::table('legacy_event_participants')->orderBy('id')->each(function (object $link): void {
                DB::table('event_participant')->insert([
                    'id' => $link->id,
                    'event_id' => $link->event_id,
                    'participant_id' => $link->participant_id,
                    'role' => $link->role,
                    'sort_order' => $link->sort_order,
                    'created_at' => $link->created_at,
                    'updated_at' => $link->updated_at,
                ]);
            });

            Schema::dropIfExists('legacy_event_participants');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participant');

        Schema::dropIfExists('event_participants');
    }
};
