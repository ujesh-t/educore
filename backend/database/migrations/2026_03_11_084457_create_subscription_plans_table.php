<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique()->nullable(); // For predefined plans: free, basic, standard, premium
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly, lifetime
            $table->json('modules'); // Array of module keys included in this plan
            $table->integer('trial_days')->default(0);
            $table->boolean('is_custom')->default(true); // false for predefined plans
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Additional settings
            $table->timestamps();

            $table->index('key');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
