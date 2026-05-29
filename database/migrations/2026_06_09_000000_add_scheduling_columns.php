<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track when the access token expires so we know when to refresh.
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('google_token_expires_at')->nullable()->after('google_refresh_token');
            $table->string('google_email')->nullable()->after('google_token_expires_at');
        });

        // Token for public reschedule/cancel deep links sent in emails.
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('token', 64)->nullable()->unique()->after('id');
            $table->string('attendee_email')->nullable()->after('lead_id');
            $table->string('attendee_name')->nullable()->after('attendee_email');
            $table->unsignedSmallInteger('duration_minutes')->default(30)->after('scheduled_at');
            $table->string('timezone', 64)->nullable()->after('duration_minutes');
        });

        // Weekly availability rules per advisor. Each row = one block on one weekday.
        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0 = Sunday … 6 = Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('slot_minutes')->default(30);
            $table->unsignedSmallInteger('buffer_minutes')->default(10);
            $table->string('timezone', 64)->default('UTC');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'weekday', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_rules');

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['token', 'attendee_email', 'attendee_name', 'duration_minutes', 'timezone']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_token_expires_at', 'google_email']);
        });
    }
};
