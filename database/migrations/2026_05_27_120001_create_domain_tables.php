<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('topic'); // applications | advising | partnerships | press | other
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('org_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->text('about');
            $table->string('role');
            $table->string('organization');
            $table->string('contact_kind'); // email | phone
            $table->string('contact_value');
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();

            // Step 1 — where you are now
            $table->string('status_self'); // student-final | graduate-recent | professional | senior | other
            $table->string('status_other')->nullable();
            $table->string('location');
            $table->string('field');

            // Step 2 — where you want to go
            $table->string('goal'); // study-abroad | local-job | international-placement | pivot | postgrad-gh | other
            $table->string('goal_other')->nullable();
            $table->jsonb('targets')->nullable();
            $table->string('timeline'); // 0-3 | 3-6 | 6-12 | 12+
            $table->string('budget');   // self | scholarship | employer | unsure

            // Step 3 — story
            $table->text('story')->nullable();

            // Step 4 — newsletter
            $table->boolean('newsletter')->default(false);

            $table->timestamps();
        });

        Schema::create('application_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('path'); // storage path
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scheduled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('calendly_event_url')->nullable();
            $table->string('status')->default('scheduled'); // scheduled | completed | no_show | canceled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('application_files');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('org_inquiries');
        Schema::dropIfExists('contact_messages');
    }
};
