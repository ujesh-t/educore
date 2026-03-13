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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Link to user account
            $table->string('student_id')->unique(); // Admission number
            $table->foreignId('class_id')->nullable()->constrained()->onDelete('set null'); // Current class
            $table->foreignId('parent_id')->nullable()->constrained('users')->onDelete('set null'); // Parent user
            $table->string('parent_name')->nullable();
            $table->string('parent_phone')->nullable();
            $table->string('parent_email')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->text('medical_info')->nullable();
            $table->date('admission_date')->nullable();
            $table->date('graduation_year')->nullable();
            $table->enum('status', ['active', 'inactive', 'graduated', 'transferred'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
