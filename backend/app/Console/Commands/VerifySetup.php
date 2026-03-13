<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Console\Command;

class VerifySetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verify-setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify the EduCore setup is complete';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== EduCore Setup Verification ===');
        $this->newLine();

        // Test 1: Database connection
        $this->info('1. Testing database connection...');
        $count = User::count();
        $this->line("   ✓ Database connected. Users: $count");
        $this->newLine();

        // Test 2: Roles
        $this->info('2. Checking roles...');
        $roles = Role::all();
        foreach ($roles as $role) {
            $this->line("   - {$role->name}: {$role->description}");
        }
        $this->newLine();

        // Test 3: Users
        $this->info('3. Checking seeded users...');
        $users = User::with('role')->get();
        foreach ($users as $user) {
            $this->line("   - {$user->name} ({$user->email}) - Role: {$user->role->name}");
        }
        $this->newLine();

        // Test 4: Settings
        $this->info('4. Checking system settings...');
        $count = Setting::count();
        $this->line("   ✓ Settings configured: $count");
        $this->newLine();

        $this->info('=== All Tests Completed ===');
        $this->newLine();
        $this->info('Default login credentials:');
        $this->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@edupro.com', 'password123'],
                ['Teacher', 'teacher@edupro.com', 'password123'],
                ['Student', 'student@edupro.com', 'password123'],
                ['Parent', 'parent@edupro.com', 'password123'],
                ['Staff', 'staff@edupro.com', 'password123'],
            ]
        );

        return self::SUCCESS;
    }
}
