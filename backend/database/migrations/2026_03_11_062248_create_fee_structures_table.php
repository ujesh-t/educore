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
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Grade 10 Monthly Fee"
            $table->foreignId('class_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('fee_type', ['tuition', 'exam', 'transport', 'library', 'lab', 'sports', 'hostel', 'miscellaneous']);
            $table->decimal('amount', 10, 2);
            $table->enum('frequency', ['one-time', 'monthly', 'quarterly', 'yearly', 'per_term']);
            $table->string('academic_year')->nullable();
            $table->string('term')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
