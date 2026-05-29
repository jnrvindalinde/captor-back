<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->text('quote')->nullable()->after('summary');
            $table->string('outcome_label', 200)->nullable()->after('outcome');
            $table->json('categories')->nullable()->after('outcome_label');
            $table->json('gallery')->nullable()->after('cover_image');
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn(['quote', 'outcome_label', 'categories', 'gallery']);
        });
    }
};
