<?php

// Simple test script to verify API functionality

require __DIR__.'/backend/vendor/autoload.php';

$app = require_once __DIR__.'/backend/bootstrap/app.php';

use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

echo "=== EduCore API Test ===\n\n";

// Test 1: Check database connection
echo "1. Testing database connection...\n";
try {
    $count = User::count();
    echo "   ✓ Database connected. Users found: $count\n";
} catch (\Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check roles
echo "\n2. Checking roles...\n";
$roles = Role::all();
foreach ($roles as $role) {
    echo "   - {$role->name}: {$role->description}\n";
}

// Test 3: Check users
echo "\n3. Checking seeded users...\n";
$users = User::with('role')->get();
foreach ($users as $user) {
    echo "   - {$user->name} ({$user->email}) - Role: {$user->role->name}\n";
}

// Test 4: Test authentication manually
echo "\n4. Testing authentication logic...\n";
$admin = User::where('email', 'admin@edupro.com')->first();
if ($admin && Hash::check('password123', $admin->password)) {
    echo "   ✓ Admin credentials verified\n";
} else {
    echo "   ✗ Admin credentials check failed\n";
}

// Test 5: Check settings
echo "\n5. Checking system settings...\n";
$settingsCount = \App\Models\Setting::count();
echo "   ✓ Settings configured: $settingsCount\n";

echo "\n=== All Tests Completed ===\n";
echo "\nDefault login credentials:\n";
echo "  Admin:   admin@edupro.com / password123\n";
echo "  Teacher: teacher@edupro.com / password123\n";
echo "  Student: student@edupro.com / password123\n";
echo "  Parent:  parent@edupro.com / password123\n";
echo "  Staff:   staff@edupro.com / password123\n";
