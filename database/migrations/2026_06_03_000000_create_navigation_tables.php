<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();          // primary | footer-explore | footer-resources ...
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->string('label_en');
            $table->string('label_fr')->nullable();
            $table->string('href', 1024);
            $table->string('target', 16)->default('_self'); // _self | _blank
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->index(['menu_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('navigation_menus');
    }
};
