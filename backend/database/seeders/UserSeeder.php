<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $teacherRole = Role::where('name', 'teacher')->first();
        $staffRole = Role::where('name', 'staff')->first();
        $studentRole = Role::where('name', 'student')->first();
        $parentRole = Role::where('name', 'parent')->first();

        // Create Admin User
        User::firstOrCreate(
            ['email' => 'admin@edupro.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id,
                'phone' => '+1234567890',
                'is_active' => true,
            ]
        );

        // Create Principal User
        User::firstOrCreate(
            ['email' => 'principal@edupro.com'],
            [
                'name' => 'John Principal',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id, // Using admin role for principal
                'phone' => '+1234567891',
                'is_active' => true,
            ]
        );

        // Create Teacher User
        User::firstOrCreate(
            ['email' => 'teacher@edupro.com'],
            [
                'name' => 'Jane Teacher',
                'password' => Hash::make('password123'),
                'role_id' => $teacherRole->id,
                'phone' => '+1234567892',
                'is_active' => true,
            ]
        );

        // Create Staff User
        User::firstOrCreate(
            ['email' => 'staff@edupro.com'],
            [
                'name' => 'Bob Staff',
                'password' => Hash::make('password123'),
                'role_id' => $staffRole->id,
                'phone' => '+1234567893',
                'is_active' => true,
            ]
        );

        // Create Student User
        User::firstOrCreate(
            ['email' => 'student@edupro.com'],
            [
                'name' => 'Alice Student',
                'password' => Hash::make('password123'),
                'role_id' => $studentRole->id,
                'phone' => '+1234567894',
                'is_active' => true,
            ]
        );

        // Create Parent User
        User::firstOrCreate(
            ['email' => 'parent@edupro.com'],
            [
                'name' => 'Charlie Parent',
                'password' => Hash::make('password123'),
                'role_id' => $parentRole->id,
                'phone' => '+1234567895',
                'is_active' => true,
            ]
        );
    }
}
