<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->unique(); // TXN-2026-000001
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('payment_method')->default('cash'); // cash, card, online, bank_transfer, cheque
            $table->string('status')->default('completed'); // pending, completed, failed, refunded
            $table->date('payment_date');
            $table->string('reference_number')->nullable(); // Cheque number, UTR, etc.
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Payment gateway response, etc.
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index(['school_id', 'payment_date']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
