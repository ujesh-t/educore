<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolModule;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default school (EduCore HQ)
        $defaultSchool = School::firstOrCreate(
            ['code' => 'EDUCORE-HQ'],
            [
                'name' => 'EduCore Headquarters',
                'subdomain' => 'hq',
                'email' => 'hq@edupro.com',
                'phone' => '+1-555-0100',
                'country' => 'US',
                'timezone' => 'America/New_York',
                'is_active' => true,
            ]
        );

        // Create demo school
        $demoSchool = School::firstOrCreate(
            ['code' => 'DEMO-SCHOOL'],
            [
                'name' => 'Demo High School',
                'subdomain' => 'demo',
                'email' => 'info@demohigh.edu',
                'phone' => '+1-555-0200',
                'address' => '123 Education Street',
                'city' => 'Springfield',
                'state' => 'IL',
                'country' => 'US',
                'timezone' => 'America/Chicago',
                'is_active' => true,
            ]
        );

        // Create super admin user (not tied to a specific school)
        User::firstOrCreate(
            ['email' => 'superadmin@edupro.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password123'),
                'role_id' => Role::where('name', 'super_admin')->first()?->id,
                'phone' => '+1-555-0001',
                'is_active' => true,
                'is_super_admin' => true,
            ]
        );

        // Create school admin for default school
        User::firstOrCreate(
            ['email' => 'admin@edupro.com'],
            [
                'school_id' => $defaultSchool->id,
                'name' => 'System Administrator',
                'password' => Hash::make('password123'),
                'role_id' => Role::where('name', 'admin')->first()?->id,
                'phone' => '+1-555-0101',
                'is_active' => true,
            ]
        );

        // Create users for demo school
        $adminRole = Role::where('name', 'admin')->first();
        $teacherRole = Role::where('name', 'teacher')->first();
        $staffRole = Role::where('name', 'staff')->first();
        $studentRole = Role::where('name', 'student')->first();
        $parentRole = Role::where('name', 'parent')->first();

        // Demo School Admin
        User::firstOrCreate(
            ['email' => 'admin@demohigh.edu'],
            [
                'school_id' => $demoSchool->id,
                'name' => 'Demo Admin',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole?->id,
                'phone' => '+1-555-0201',
                'is_active' => true,
            ]
        );

        // Demo Teacher
        User::firstOrCreate(
            ['email' => 'teacher@demohigh.edu'],
            [
                'school_id' => $demoSchool->id,
                'name' => 'Jane Teacher',
                'password' => Hash::make('password123'),
                'role_id' => $teacherRole?->id,
                'phone' => '+1-555-0202',
                'is_active' => true,
            ]
        );

        // Demo Staff
        User::firstOrCreate(
            ['email' => 'staff@demohigh.edu'],
            [
                'school_id' => $demoSchool->id,
                'name' => 'Bob Staff',
                'password' => Hash::make('password123'),
                'role_id' => $staffRole?->id,
                'phone' => '+1-555-0203',
                'is_active' => true,
            ]
        );

        // Demo Student
        User::firstOrCreate(
            ['email' => 'student@demohigh.edu'],
            [
                'school_id' => $demoSchool->id,
                'name' => 'Alice Student',
                'password' => Hash::make('password123'),
                'role_id' => $studentRole?->id,
                'phone' => '+1-555-0204',
                'is_active' => true,
            ]
        );

        // Demo Parent
        User::firstOrCreate(
            ['email' => 'parent@demohigh.edu'],
            [
                'school_id' => $demoSchool->id,
                'name' => 'Charlie Parent',
                'password' => Hash::make('password123'),
                'role_id' => $parentRole?->id,
                'phone' => '+1-555-0205',
                'is_active' => true,
            ]
        );

        // Create subscription for demo school (Premium plan)
        Subscription::firstOrCreate(
            ['school_id' => $demoSchool->id],
            [
                'plan' => 'premium',
                'status' => 'active',
                'amount' => 99.00,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_ends_at' => null,
                'starts_at' => now(),
            ]
        );

        // Enable all modules for demo school
        $modules = Module::all();
        foreach ($modules as $module) {
            SchoolModule::firstOrCreate(
                [
                    'school_id' => $demoSchool->id,
                    'module_id' => $module->id,
                ],
                [
                    'is_enabled' => $module->is_core, // Only core modules enabled by default
                ]
            );
        }

        $this->command->info('Schools and users seeded successfully!');
        $this->command->info('Super Admin: superadmin@edupro.com / password123');
        $this->command->info('Demo School Admin: admin@demohigh.edu / password123');
    }
}
