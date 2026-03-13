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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "10th Grade A"
            $table->string('grade_level'); // e.g., "10th"
            $table->string('section')->nullable(); // e.g., "A", "B"
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null'); // Class teacher
            $table->integer('capacity')->default(30);
            $table->string('room_number')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
