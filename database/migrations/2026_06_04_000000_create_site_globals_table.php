<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_globals', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('Career360 Consult');
            $table->string('tagline_en')->nullable();
            $table->string('tagline_fr')->nullable();
            $table->string('logo_light_url', 2048)->nullable();
            $table->string('logo_dark_url', 2048)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('address_en')->nullable();
            $table->text('address_fr')->nullable();
            $table->json('socials')->nullable(); // {twitter, linkedin, facebook, instagram, youtube}
            $table->string('footer_copyright_en')->nullable();
            $table->string('footer_copyright_fr')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_globals');
    }
};
