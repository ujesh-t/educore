<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->boolean('is_enabled')->default(false);
            $table->json('config')->nullable(); // School-specific module settings
            $table->timestamps();

            $table->unique(['school_id', 'module_id']);
            $table->index(['school_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_modules');
    }
};
