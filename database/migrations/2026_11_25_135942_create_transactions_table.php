<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique();
            $table->enum('type', ['income', 'expense'])->index();
            $table->date('transaction_date')->index();
            $table->decimal('amount', 15, 2);
            $table->string('source_or_purpose');
            $table->string('donor_name')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->text('description')->nullable();
            $table->enum('status', ['verified', 'cancelled'])->default('verified')->index();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('previous_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};