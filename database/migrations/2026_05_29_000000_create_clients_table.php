<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clients table — represents active engagements that have moved past the lead
 * stage. A client may originate from a `Lead` (via `source_lead_id`) when an
 * application is won, or be created directly (e.g. for org partnerships).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('program'); // study-abroad | scholarship | career-coaching | test-prep | org-partnership
            $table->foreignId('consultant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('onboarding'); // onboarding | active | on_hold | completed | churned
            $table->timestamp('start_date');
            $table->string('next_milestone_label')->nullable();
            $table->timestamp('next_milestone_due_at')->nullable();
            $table->unsignedTinyInteger('satisfaction')->nullable(); // 1..5
            $table->foreignId('source_lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
