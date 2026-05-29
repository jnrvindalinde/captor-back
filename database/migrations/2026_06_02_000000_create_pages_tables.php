<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('kind', 24)->default('landing'); // marketing | landing
            $table->string('status', 16)->default('draft'); // draft | published
            $table->string('title_en');
            $table->string('title_fr')->nullable();
            $table->string('seo_title_en')->nullable();
            $table->string('seo_title_fr')->nullable();
            $table->text('seo_description_en')->nullable();
            $table->text('seo_description_fr')->nullable();
            $table->foreignId('og_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('type', 64);            // see SectionRegistry
            $table->unsignedInteger('position')->default(0);
            $table->string('status', 16)->default('published'); // draft | published
            $table->json('data');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['page_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('pages');
    }
};
