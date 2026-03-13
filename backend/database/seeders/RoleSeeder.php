<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'description' => 'Super Admin - Full system access',
                'permissions' => ['*'], // All permissions
                'is_active' => true,
            ],
            [
                'name' => 'principal',
                'description' => 'School Principal - View dashboard, approve finances, manage staff',
                'permissions' => ['view_dashboard', 'approve_finances', 'manage_staff', 'view_reports'],
                'is_active' => true,
            ],
            [
                'name' => 'teacher',
                'description' => 'Teacher - Manage subjects, grades, attendance, assignments',
                'permissions' => ['view_dashboard', 'manage_subjects', 'manage_grades', 'manage_attendance', 'manage_assignments'],
                'is_active' => true,
            ],
            [
                'name' => 'staff',
                'description' => 'Administrative Staff - Manage fees, admissions, transport, inventory',
                'permissions' => ['view_dashboard', 'manage_fees', 'manage_admissions', 'manage_transport', 'manage_inventory'],
                'is_active' => true,
            ],
            [
                'name' => 'student',
                'description' => 'Student - View results, fee status, announcements',
                'permissions' => ['view_dashboard', 'view_results', 'view_fees', 'view_announcements'],
                'is_active' => true,
            ],
            [
                'name' => 'parent',
                'description' => 'Parent - View child progress, fee history, communicate with teachers',
                'permissions' => ['view_dashboard', 'view_child_results', 'view_child_fees', 'message_teachers'],
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
