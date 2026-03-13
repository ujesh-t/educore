<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // students, attendance, fees, etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // emoji or icon class
            $table->string('route_prefix')->nullable(); // e.g., 'students', 'fees'
            $table->boolean('is_core')->default(false); // Core modules always enabled
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable(); // Module-specific settings
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('key');
            $table->index('is_core');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
