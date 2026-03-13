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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('assignment_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->decimal('marks_obtained', 8, 2);
            $table->decimal('max_marks', 8, 2)->default(100);
            $table->decimal('weightage', 5, 2)->default(100); // Percentage weightage
            $table->string('grade')->nullable(); // A, B, C, etc.
            $table->text('remarks')->nullable();
            $table->string('academic_year')->nullable();
            $table->string('term')->nullable(); // Term 1, Term 2, etc.
            $table->timestamps();
            
            $table->index(['student_id', 'academic_year', 'term']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
