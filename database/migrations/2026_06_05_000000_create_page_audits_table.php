<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('page_audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('page_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 64);
            $t->json('payload')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['page_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_audits');
    }
};
