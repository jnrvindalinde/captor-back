<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proposal alignment — SAH-BD-20260428-PRO-51:
 *  - Swap Calendly → Google Calendar + Google Meet on meetings
 *  - Add approve/decline workflow to applications + leads
 *  - Introduce CMS tables: posts (blog), resources (library), stories (showcase)
 */
return new class extends Migration
{
    public function up(): void
    {
        /* ----- Google Calendar / Meet ----- */
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_calendar_id')->nullable()->after('calendly_url');
            $table->text('google_refresh_token')->nullable()->after('google_calendar_id');
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('calendly_event_url');
            $table->string('google_event_id')->nullable()->after('scheduled_by');
            $table->string('google_meet_link')->nullable()->after('google_event_id');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('calendly_url');
        });

        /* ----- Application decision workflow ----- */
        Schema::table('applications', function (Blueprint $table) {
            $table->string('decision')->default('pending')->after('newsletter'); // pending | approved | declined
            $table->text('decision_note')->nullable()->after('decision');
            $table->timestamp('decided_at')->nullable()->after('decision_note');
            $table->foreignId('decided_by')->nullable()->after('decided_at')
                ->constrained('users')->nullOnDelete();
        });

        /* ----- Blog posts ----- */
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('excerpt', 500)->nullable();
            $table->longText('body')->nullable();
            $table->string('cover_image')->nullable(); // Cloudinary URL or local path
            $table->string('status')->default('draft'); // draft | published
            $table->jsonb('tags')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        /* ----- Resource library ----- */
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('format'); // guide | document | video | audio | external
            $table->string('file_path')->nullable(); // for hosted assets
            $table->string('external_url')->nullable(); // for external links / embeds
            $table->string('cover_image')->nullable();
            $table->string('status')->default('draft'); // draft | published
            $table->jsonb('tags')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'format']);
        });

        /* ----- Success stories ----- */
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('summary', 500)->nullable();
            $table->longText('body')->nullable();
            $table->string('person_name');
            $table->string('person_role')->nullable();
            $table->string('outcome')->nullable(); // admission | scholarship | placement | transition | achievement
            $table->string('cover_image')->nullable();
            $table->string('status')->default('draft'); // draft | published
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('posts');

        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decided_by');
            $table->dropColumn(['decision', 'decision_note', 'decided_at']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->string('calendly_url')->nullable();
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['google_event_id', 'google_meet_link']);
            $table->string('calendly_event_url')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_calendar_id', 'google_refresh_token']);
        });
    }
};
