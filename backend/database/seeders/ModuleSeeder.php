<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            // Core modules (always enabled) - These are also free modules
            [
                'key' => 'dashboard',
                'name' => 'Dashboard',
                'description' => 'Main dashboard and overview',
                'icon' => '📊',
                'route_prefix' => 'dashboard',
                'is_core' => true,
                'is_active' => true,
                'is_free_module' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'communication',
                'name' => 'Communication',
                'description' => 'Announcements and messaging',
                'icon' => '💬',
                'route_prefix' => 'communication',
                'is_core' => true,
                'is_active' => true,
                'is_free_module' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'profile',
                'name' => 'Profile',
                'description' => 'User profile and settings',
                'icon' => '👤',
                'route_prefix' => 'profile',
                'is_core' => true,
                'is_active' => true,
                'is_free_module' => true,
                'sort_order' => 3,
            ],

            // Student Management
            [
                'key' => 'students',
                'name' => 'Student Management',
                'description' => 'Student enrollment, profiles, and records',
                'icon' => '👨‍🎓',
                'route_prefix' => 'students',
                'is_core' => false,
                'is_active' => true,
                'is_free_module' => false,
                'sort_order' => 10,
            ],

            // Attendance
            [
                'key' => 'attendance',
                'name' => 'Attendance',
                'description' => 'Daily attendance tracking',
                'icon' => '✅',
                'route_prefix' => 'attendance',
                'is_core' => false,
                'is_active' => true,
                'is_free_module' => false,
                'sort_order' => 11,
            ],

            // Academics
            [
                'key' => 'academics',
                'name' => 'Academics',
                'description' => 'Classes, subjects, and timetables',
                'icon' => '📚',
                'route_prefix' => 'academics',
                'is_core' => false,
                'is_active' => true,
                'is_free_module' => false,
                'sort_order' => 20,
            ],

            // Examinations
            [
                'key' => 'examinations',
                'name' => 'Examinations',
                'description' => 'Exams, grades, and report cards',
                'icon' => '📝',
                'route_prefix' => 'examinations',
                'is_core' => false,
                'is_active' => true,
                'is_free_module' => false,
                'sort_order' => 21,
            ],

            // Fees
            [
                'key' => 'fees',
                'name' => 'Fee Management',
                'description' => 'Fee structure, invoices, and payments',
                'icon' => '💰',
                'route_prefix' => 'fees',
                'is_core' => false,
                'is_active' => true,
                'is_free_module' => false,
                'sort_order' => 30,
            ],

            // Transport
            [
                'key' => 'transport',
                'name' => 'Transport',
                'description' => 'Bus routes and vehicle management',
                'icon' => '🚌',
                'route_prefix' => 'transport',
                'is_core' => false,
                'is_active' => true,
                'is_free_module' => false,
                'sort_order' => 40,
            ],

            // Hostel
            [
                'key' => 'hostel',
                'name' => 'Hostel',
                'description' => 'Room allocation and hostel management',
                'icon' => '🏠',
                'route_prefix' => 'hostel',
                'is_core' => false,
                'is_active' => true,
                'is_free_module' => false,
                'sort_order' => 41,
            ],

            // Library
            [
                'key' => 'library',
                'name' => 'Library',
                'description' => 'Book management and issue/return',
                'icon' => '📖',
                'route_prefix' => 'library',
                'is_core' => false,
                'is_active' => true,
                'is_free_module' => false,
                'sort_order' => 42,
            ],

            // Inventory
            [
                'key' => 'inventory',
                'name' => 'Inventory',
                'description' => 'Asset and stock management',
                'icon' => '📦',
                'route_prefix' => 'inventory',
                'is_core' => false,
                'is_active' => true,
                'is_free_module' => false,
                'sort_order' => 50,
            ],

            // Payroll
            [
                'key' => 'payroll',
                'name' => 'Payroll',
                'description' => 'Staff salary management',
                'icon' => '💵',
                'route_prefix' => 'payroll',
                'is_core' => false,
                'is_active' => true,
                'is_free_module' => false,
                'sort_order' => 51,
            ],

            // Reports
            [
                'key' => 'reports',
                'name' => 'Reports',
                'description' => 'Analytics and custom reports',
                'icon' => '📈',
                'route_prefix' => 'reports',
                'is_core' => false,
                'is_active' => true,
                'is_free_module' => false,
                'sort_order' => 60,
            ],
        ];

        foreach ($modules as $moduleData) {
            Module::updateOrCreate(
                ['key' => $moduleData['key']],
                $moduleData
            );
        }

        $this->command->info('Modules seeded successfully!');
    }
}
