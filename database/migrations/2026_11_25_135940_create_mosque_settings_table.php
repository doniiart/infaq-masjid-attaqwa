<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mosque_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Masjid At-Taqwa');
            $table->text('address');
            $table->string('email')->default('masjidattaqwa2309@gmail.com');
            $table->string('phone')->nullable();
            $table->json('bank_accounts')->nullable();
            $table->text('about_description')->nullable();
            $table->string('logo_path')->default('/images/logo-masjid-attaqwa.png');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mosque_settings');
    }
};