<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            // Discriminator for the originating form.
            $table->string('kind'); // contact | org | application
            // Pipeline stage for the admin portal.
            $table->string('status')->default('new'); // new | contacted | scheduled | qualified | won | lost
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Denormalized contact info for fast list/search across kinds.
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('source')->nullable();

            // Scheduling.
            $table->string('calendly_url')->nullable();
            $table->timestamp('scheduled_at')->nullable();

            // Free-form admin tags (JSON array of strings).
            $table->jsonb('tags')->nullable();

            $table->timestamps();

            $table->index(['kind', 'status']);
            $table->index('email');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
