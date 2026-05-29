<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('provider', 32)->default('cloudinary');
            $table->string('public_id');
            $table->string('secure_url', 1024);
            $table->string('format', 16)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('bytes')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('folder')->nullable();
            $table->string('alt_en')->nullable();
            $table->string('alt_fr')->nullable();
            $table->text('caption_en')->nullable();
            $table->text('caption_fr')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('public_id');
            $table->index('folder');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
