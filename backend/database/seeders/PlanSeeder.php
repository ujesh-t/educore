<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'key' => 'free',
                'description' => 'Starter plan for small schools',
                'price' => 0,
                'currency' => 'INR',
                'billing_cycle' => 'monthly',
                'modules' => ['students', 'attendance', 'communication'],
                'trial_days' => 0,
                'is_custom' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Basic',
                'key' => 'basic',
                'description' => 'Essential features for growing schools',
                'price' => 499,
                'currency' => 'INR',
                'billing_cycle' => 'monthly',
                'modules' => ['students', 'attendance', 'communication', 'academics', 'examinations', 'fees'],
                'trial_days' => 14,
                'is_custom' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Standard',
                'key' => 'standard',
                'description' => 'Advanced features for established schools',
                'price' => 999,
                'currency' => 'INR',
                'billing_cycle' => 'monthly',
                'modules' => ['students', 'attendance', 'communication', 'academics', 'examinations', 'fees', 'transport', 'hostel', 'library'],
                'trial_days' => 14,
                'is_custom' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Premium',
                'key' => 'premium',
                'description' => 'All features for large institutions',
                'price' => 1999,
                'currency' => 'INR',
                'billing_cycle' => 'monthly',
                'modules' => ['students', 'attendance', 'communication', 'academics', 'examinations', 'fees', 'transport', 'hostel', 'library', 'inventory', 'payroll', 'reports'],
                'trial_days' => 30,
                'is_custom' => false,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::updateOrCreate(
                ['key' => $planData['key']],
                $planData
            );
        }

        $this->command->info('Plans seeded successfully!');
    }
}
