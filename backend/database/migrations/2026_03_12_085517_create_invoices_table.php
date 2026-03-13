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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
            $table->string('invoice_number')->unique(); // INV-2026-000001
            $table->string('status')->default('pending'); // pending, paid, overdue, cancelled
            $table->string('type')->default('subscription'); // subscription, one_time, credit, debit
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->string('currency')->default('INR');
            $table->string('billing_cycle')->default('monthly'); // monthly, quarterly, yearly
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('billing_period_start')->nullable();
            $table->date('billing_period_end')->nullable();
            $table->date('paid_at')->nullable();
            $table->string('payment_method')->nullable(); // cash, card, online, bank_transfer, cheque
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional invoice data
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['invoice_date', 'due_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
