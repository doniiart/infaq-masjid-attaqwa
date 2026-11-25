<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('construction_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('construction_stage_id')->nullable()->constrained('construction_stages')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('documented_at')->index();
            $table->boolean('is_published')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('construction_documents');
    }
};