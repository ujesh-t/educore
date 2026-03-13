<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // School Settings
            ['key' => 'school_name', 'value' => 'EduCore School', 'type' => 'string', 'group' => 'school', 'description' => 'Official school name', 'is_public' => true],
            ['key' => 'school_address', 'value' => '123 Education Street, Knowledge City', 'type' => 'string', 'group' => 'school', 'description' => 'School address', 'is_public' => true],
            ['key' => 'school_phone', 'value' => '+1234567890', 'type' => 'string', 'group' => 'school', 'description' => 'School contact number', 'is_public' => true],
            ['key' => 'school_email', 'value' => 'info@edupro.com', 'type' => 'string', 'group' => 'school', 'description' => 'School email address', 'is_public' => true],
            ['key' => 'academic_year', 'value' => '2026', 'type' => 'string', 'group' => 'school', 'description' => 'Current academic year', 'is_public' => false],
            ['key' => 'current_term', 'value' => 'Term 1', 'type' => 'string', 'group' => 'school', 'description' => 'Current term', 'is_public' => false],
            
            // System Settings
            ['key' => 'session_timeout_minutes', 'value' => '15', 'type' => 'number', 'group' => 'system', 'description' => 'Session timeout in minutes', 'is_public' => false],
            ['key' => 'allow_registration', 'value' => 'true', 'type' => 'boolean', 'group' => 'system', 'description' => 'Allow user registration', 'is_public' => false],
            ['key' => 'require_email_verification', 'value' => 'false', 'type' => 'boolean', 'group' => 'system', 'description' => 'Require email verification', 'is_public' => false],
            
            // Fee Settings
            ['key' => 'late_fee_percentage', 'value' => '5', 'type' => 'number', 'group' => 'fees', 'description' => 'Late fee percentage', 'is_public' => false],
            ['key' => 'grace_period_days', 'value' => '7', 'type' => 'number', 'group' => 'fees', 'description' => 'Grace period for fee payment in days', 'is_public' => false],
            
            // Attendance Settings
            ['key' => 'attendance_cutoff_time', 'value' => '09:30', 'type' => 'string', 'group' => 'attendance', 'description' => 'Time after which attendance is marked late', 'is_public' => false],
            
            // Payment Settings
            ['key' => 'payment_gateway_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'payment', 'description' => 'Enable online payment gateway', 'is_public' => false],
            ['key' => 'stripe_api_key', 'value' => '', 'type' => 'string', 'group' => 'payment', 'description' => 'Stripe API key', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
