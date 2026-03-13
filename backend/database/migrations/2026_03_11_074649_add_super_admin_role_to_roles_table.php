<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_active');
        });

        // Create super admin role
        Role::firstOrCreate(
            ['name' => 'super_admin'],
            [
                'description' => 'System Super Administrator - Full access to all schools and settings',
                'is_system' => true,
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });

        Role::where('name', 'super_admin')->delete();
    }
};
