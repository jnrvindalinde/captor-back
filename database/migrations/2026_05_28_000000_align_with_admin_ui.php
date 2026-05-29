<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Align the backend schema with the admin UI:
 *   - Adds a UUID column to `leads` (used for admin URLs).
 *   - Adds a `kind` column to `notes` (manual | system) so we can render
 *     audit-trail entries differently from user-authored notes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Backfill any existing rows before locking the column down.
        DB::table('leads')->whereNull('uuid')->orderBy('id')->each(function ($lead) {
            DB::table('leads')->where('id', $lead->id)->update([
                'uuid' => (string) Str::uuid(),
            ]);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
            $table->unique('uuid');
        });

        Schema::table('notes', function (Blueprint $table) {
            // manual = written by an admin; system = auto-emitted on lifecycle events.
            $table->string('kind')->default('manual')->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn('kind');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
